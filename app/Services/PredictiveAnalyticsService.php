<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Student;
use App\Models\StudentRiskPrediction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PredictiveAnalyticsService
{
    // ==========================================
    // Weight Constants (total = 1.0)
    // ==========================================

    public const WEIGHT_ACADEMIC = 0.40;

    public const WEIGHT_BEHAVIORAL = 0.30;

    public const WEIGHT_ENGAGEMENT = 0.20;

    public const WEIGHT_CONTEXTUAL = 0.10;

    // ==========================================
    // Risk Factor Thresholds
    // ==========================================

    // Academic
    public const LOW_AVERAGE_THRESHOLD = 60; // Below this is risky

    public const DECLINING_TREND_THRESHOLD = -10; // Score drop of 10+ points

    public const FAILING_RATIO_THRESHOLD = 0.3; // 30%+ fail rate is risky

    // Behavioral
    public const HIGH_VIOLATION_THRESHOLD = 5; // 5+ violations total

    public const VIOLATION_PER_EXAM_THRESHOLD = 2; // 2+ violations per exam average

    // Engagement
    public const POOR_ATTENDANCE_THRESHOLD = 0.7; // Below 70% attendance

    public const RUSHING_TIME_THRESHOLD = 0.3; // Using less than 30% of time

    // Cache duration in minutes
    public const CACHE_DURATION = 60;

    // ==========================================
    // Main Prediction Methods
    // ==========================================

    /**
     * Calculate risk score for a student (general or exam-specific)
     */
    public function calculateRiskScore(Student $student, ?Exam $upcomingExam = null): array
    {
        $cacheKey = "risk_score_{$student->id}_".($upcomingExam?->id ?? 'general');

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($student, $upcomingExam) {
            // Gather historical data
            $historicalData = $this->gatherHistoricalData($student, $upcomingExam);

            // Calculate individual risk components
            $academicRisk = $this->calculateAcademicRisk($historicalData);
            $behavioralRisk = $this->calculateBehavioralRisk($historicalData);
            $engagementRisk = $this->calculateEngagementRisk($historicalData);
            $contextualRisk = $this->calculateContextualRisk($student, $upcomingExam, $historicalData);

            // Calculate weighted total
            $totalRiskScore = ($academicRisk['score'] * self::WEIGHT_ACADEMIC)
                + ($behavioralRisk['score'] * self::WEIGHT_BEHAVIORAL)
                + ($engagementRisk['score'] * self::WEIGHT_ENGAGEMENT)
                + ($contextualRisk['score'] * self::WEIGHT_CONTEXTUAL);

            // Determine risk level
            $riskLevel = StudentRiskPrediction::getRiskLevelFromScore($totalRiskScore);

            // Predict score based on historical data
            $predictedScore = $this->predictScore($historicalData);

            // Identify weak topics
            $weakTopics = $this->identifyWeakTopics($student, $upcomingExam);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($totalRiskScore, [
                'academic' => $academicRisk,
                'behavioral' => $behavioralRisk,
                'engagement' => $engagementRisk,
                'contextual' => $contextualRisk,
            ]);

            return [
                'risk_score' => round($totalRiskScore, 2),
                'risk_level' => $riskLevel,
                'risk_factors' => [
                    'academic' => [
                        'score' => round($academicRisk['score'], 2),
                        'weight' => self::WEIGHT_ACADEMIC,
                        'factors' => $academicRisk['factors'],
                    ],
                    'behavioral' => [
                        'score' => round($behavioralRisk['score'], 2),
                        'weight' => self::WEIGHT_BEHAVIORAL,
                        'factors' => $behavioralRisk['factors'],
                    ],
                    'engagement' => [
                        'score' => round($engagementRisk['score'], 2),
                        'weight' => self::WEIGHT_ENGAGEMENT,
                        'factors' => $engagementRisk['factors'],
                    ],
                    'contextual' => [
                        'score' => round($contextualRisk['score'], 2),
                        'weight' => self::WEIGHT_CONTEXTUAL,
                        'factors' => $contextualRisk['factors'],
                    ],
                ],
                'predicted_score' => $predictedScore,
                'weak_topics' => $weakTopics,
                'recommended_actions' => $recommendations,
                'historical_data' => [
                    'average' => $historicalData['average'],
                    'total_exams' => $historicalData['total_exams'],
                    'passed' => $historicalData['passed'],
                    'failed' => $historicalData['failed'],
                    'total_violations' => $historicalData['total_violations'],
                ],
            ];
        });
    }

    /**
     * Generate predictions for all students taking an upcoming exam
     */
    public function generatePredictionsForExam(Exam $exam): Collection
    {
        // Get all students eligible for this exam (from the classroom)
        $students = Student::where('classroom_id', $exam->classroom_id)
            ->active()
            ->get();

        $predictions = collect();

        foreach ($students as $student) {
            $riskData = $this->calculateRiskScore($student, $exam);

            $prediction = StudentRiskPrediction::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'exam_id' => $exam->id,
                ],
                [
                    'lesson_id' => $exam->lesson_id,
                    'risk_score' => $riskData['risk_score'],
                    'risk_level' => $riskData['risk_level'],
                    'risk_factors' => $riskData['risk_factors'],
                    'predicted_score' => $riskData['predicted_score'],
                    'weak_topics' => $riskData['weak_topics'],
                    'recommended_actions' => $riskData['recommended_actions'],
                    'historical_average' => $riskData['historical_data']['average'],
                    'total_exams_taken' => $riskData['historical_data']['total_exams'],
                    'total_passed' => $riskData['historical_data']['passed'],
                    'total_failed' => $riskData['historical_data']['failed'],
                    'total_violations' => $riskData['historical_data']['total_violations'],
                    'calculation_version' => StudentRiskPrediction::CURRENT_VERSION,
                    'expires_at' => now()->addDays(7), // Prediction valid for 7 days
                ]
            );

            $predictions->push($prediction);
        }

        return $predictions;
    }

    /**
     * Get predictions for upcoming exams (next 24 hours)
     */
    public function getUpcomingExamPredictions(): Collection
    {
        // Get exam sessions starting in the next 24 hours
        $upcomingSessions = ExamSession::where('start_time', '>', now())
            ->where('start_time', '<=', now()->addHours(24))
            ->with('exam')
            ->get();

        $allPredictions = collect();

        foreach ($upcomingSessions as $session) {
            if ($session->exam) {
                $predictions = $this->generatePredictionsForExam($session->exam);
                $allPredictions = $allPredictions->merge($predictions);
            }
        }

        return $allPredictions;
    }

    // ==========================================
    // Data Gathering
    // ==========================================

    /**
     * Gather historical performance data for a student
     */
    protected function gatherHistoricalData(Student $student, ?Exam $upcomingExam = null): array
    {
        $query = Grade::where('student_id', $student->id)->completed();

        // If exam is specified, filter by same lesson for relevant history
        if ($upcomingExam && $upcomingExam->lesson_id) {
            $query->whereHas('exam', function ($q) use ($upcomingExam) {
                $q->where('lesson_id', $upcomingExam->lesson_id);
            });
        }

        $grades = $query->orderBy('end_time', 'desc')->limit(20)->get();

        // Calculate metrics
        $totalExams = $grades->count();
        $averageScore = $grades->avg('grade') ?? 0;
        $passed = $grades->where('status', 'passed')->count();
        $failed = $grades->where('status', 'failed')->count();
        $totalViolations = $grades->sum('violation_count');
        $flaggedCount = $grades->where('is_flagged', true)->count();

        // Calculate trend (compare recent 5 vs previous 5)
        $recent = $grades->take(5);
        $previous = $grades->skip(5)->take(5);
        $trend = 0;

        if ($recent->count() >= 3 && $previous->count() >= 3) {
            $recentAvg = $recent->avg('grade');
            $previousAvg = $previous->avg('grade');
            $trend = $recentAvg - $previousAvg;
        }

        // Time management data
        $timeUsageRatios = $grades->map(function ($grade) {
            if ($grade->start_time && $grade->end_time && $grade->duration > 0) {
                $actualMinutes = $grade->start_time->diffInMinutes($grade->end_time);

                return $actualMinutes / $grade->duration;
            }

            return null;
        })->filter()->values();

        $avgTimeUsage = $timeUsageRatios->avg() ?? 0.5;

        return [
            'grades' => $grades,
            'total_exams' => $totalExams,
            'average' => round($averageScore, 2),
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => $totalExams > 0 ? $passed / $totalExams : 0,
            'fail_rate' => $totalExams > 0 ? $failed / $totalExams : 0,
            'total_violations' => $totalViolations,
            'avg_violations_per_exam' => $totalExams > 0 ? $totalViolations / $totalExams : 0,
            'flagged_count' => $flaggedCount,
            'trend' => round($trend, 2),
            'avg_time_usage' => round($avgTimeUsage, 2),
        ];
    }

    // ==========================================
    // Risk Calculation Methods
    // ==========================================

    /**
     * Calculate academic risk score (0-100, higher = more risky)
     */
    protected function calculateAcademicRisk(array $data): array
    {
        $score = 0;
        $factors = [];

        // No exam history = moderate risk (unknown)
        if ($data['total_exams'] === 0) {
            return [
                'score' => 40,
                'factors' => ['no_history'],
            ];
        }

        // Factor 1: Low average score (40 points max)
        if ($data['average'] < self::LOW_AVERAGE_THRESHOLD) {
            $deviation = self::LOW_AVERAGE_THRESHOLD - $data['average'];
            $score += min(40, $deviation * 0.8);
            $factors[] = 'low_average';
        }

        // Factor 2: Declining trend (25 points max)
        if ($data['trend'] < self::DECLINING_TREND_THRESHOLD) {
            $declineAmount = abs($data['trend']);
            $score += min(25, $declineAmount * 1.5);
            $factors[] = 'declining_trend';
        }

        // Factor 3: High failure rate (25 points max)
        if ($data['fail_rate'] > self::FAILING_RATIO_THRESHOLD) {
            $failureRatio = $data['fail_rate'] - self::FAILING_RATIO_THRESHOLD;
            $score += min(25, $failureRatio * 50);
            $factors[] = 'high_fail_rate';
        }

        // Factor 4: Below class average (10 points)
        // This would need classroom data - simplified here
        if ($data['average'] < 70) {
            $score += 10;
            $factors[] = 'below_passing';
        }

        return [
            'score' => min(100, $score),
            'factors' => $factors,
        ];
    }

    /**
     * Calculate behavioral risk score (0-100, higher = more risky)
     */
    protected function calculateBehavioralRisk(array $data): array
    {
        $score = 0;
        $factors = [];

        // Factor 1: High total violations (35 points max)
        if ($data['total_violations'] >= self::HIGH_VIOLATION_THRESHOLD) {
            $excessViolations = $data['total_violations'] - self::HIGH_VIOLATION_THRESHOLD;
            $score += min(35, 20 + ($excessViolations * 3));
            $factors[] = 'high_violations';
        }

        // Factor 2: High average violations per exam (30 points max)
        if ($data['avg_violations_per_exam'] > self::VIOLATION_PER_EXAM_THRESHOLD) {
            $excess = $data['avg_violations_per_exam'] - self::VIOLATION_PER_EXAM_THRESHOLD;
            $score += min(30, $excess * 15);
            $factors[] = 'frequent_violations';
        }

        // Factor 3: Previously flagged (25 points)
        if ($data['flagged_count'] > 0) {
            $score += min(25, $data['flagged_count'] * 10);
            $factors[] = 'previously_flagged';
        }

        // Factor 4: Blocked history (check student)
        // This would be checked against student->is_blocked history

        return [
            'score' => min(100, $score),
            'factors' => $factors,
        ];
    }

    /**
     * Calculate engagement risk score (0-100, higher = more risky)
     */
    protected function calculateEngagementRisk(array $data): array
    {
        $score = 0;
        $factors = [];

        // Factor 1: Poor exam attendance (40 points max)
        // This would compare scheduled vs taken exams
        // Simplified: if very few exams taken
        if ($data['total_exams'] < 3 && $data['total_exams'] > 0) {
            $score += 20;
            $factors[] = 'limited_history';
        }

        // Factor 2: Rushing through exams (35 points max)
        if ($data['avg_time_usage'] < self::RUSHING_TIME_THRESHOLD) {
            $rushSeverity = (self::RUSHING_TIME_THRESHOLD - $data['avg_time_usage']) / self::RUSHING_TIME_THRESHOLD;
            $score += min(35, $rushSeverity * 50);
            $factors[] = 'rushing_exams';
        }

        // Factor 3: Inconsistent timing (25 points max)
        // High variance in time usage
        if ($data['grades'] && $data['grades']->count() >= 3) {
            $timeVariances = $data['grades']->map(function ($grade) {
                if ($grade->start_time && $grade->end_time && $grade->duration > 0) {
                    return $grade->start_time->diffInMinutes($grade->end_time) / $grade->duration;
                }

                return null;
            })->filter()->values();

            if ($timeVariances->count() >= 3) {
                $variance = $this->calculateVariance($timeVariances->toArray());
                if ($variance > 0.3) {
                    $score += min(25, $variance * 30);
                    $factors[] = 'inconsistent_timing';
                }
            }
        }

        return [
            'score' => min(100, $score),
            'factors' => $factors,
        ];
    }

    /**
     * Calculate contextual risk score (0-100, higher = more risky)
     */
    protected function calculateContextualRisk(Student $student, ?Exam $exam, array $data): array
    {
        $score = 0;
        $factors = [];

        if (! $exam) {
            return ['score' => 0, 'factors' => []];
        }

        // Factor 1: Exam difficulty (based on class average for this lesson)
        $classAverage = $this->getClassAverageForLesson($exam->classroom_id, $exam->lesson_id);
        if ($classAverage < 65) {
            $difficulty = (65 - $classAverage) / 65 * 50;
            $score += min(50, $difficulty);
            $factors[] = 'difficult_subject';
        }

        // Factor 2: Student below class average for this subject
        $studentSubjectAverage = $this->getStudentLessonAverage($student->id, $exam->lesson_id);
        if ($studentSubjectAverage && $classAverage && $studentSubjectAverage < $classAverage - 10) {
            $gap = $classAverage - $studentSubjectAverage;
            $score += min(30, $gap * 1.5);
            $factors[] = 'below_class_average';
        }

        // Factor 3: First time taking this subject's exam
        if ($data['total_exams'] === 0) {
            $score += 20;
            $factors[] = 'first_attempt_subject';
        }

        return [
            'score' => min(100, $score),
            'factors' => $factors,
        ];
    }

    // ==========================================
    // Prediction & Recommendations
    // ==========================================

    /**
     * Predict score based on historical data
     */
    protected function predictScore(array $data): ?float
    {
        if ($data['total_exams'] === 0) {
            return null;
        }

        // Simple prediction: weighted average of recent performance + trend
        $recentGrades = $data['grades']->take(5);
        if ($recentGrades->isEmpty()) {
            return $data['average'];
        }

        // Weight more recent exams higher
        $weights = [0.35, 0.25, 0.20, 0.12, 0.08];
        $weightedSum = 0;
        $totalWeight = 0;

        foreach ($recentGrades as $index => $grade) {
            $weight = $weights[$index] ?? 0.05;
            $weightedSum += $grade->grade * $weight;
            $totalWeight += $weight;
        }

        $weightedAverage = $totalWeight > 0 ? $weightedSum / $totalWeight : $data['average'];

        // Adjust for trend
        $trendAdjustment = $data['trend'] * 0.3; // Apply 30% of the trend

        $predicted = $weightedAverage + $trendAdjustment;

        // Clamp to 0-100
        return round(max(0, min(100, $predicted)), 2);
    }

    /**
     * Identify weak topics based on answer history
     */
    protected function identifyWeakTopics(Student $student, ?Exam $exam): array
    {
        if (! $exam) {
            return [];
        }

        // Get incorrect answers for this lesson
        $incorrectAnswers = Answer::where('student_id', $student->id)
            ->where('is_correct', 'N')
            ->whereHas('question', function ($q) use ($exam) {
                $q->whereHas('exam', function ($e) use ($exam) {
                    $e->where('lesson_id', $exam->lesson_id);
                });
            })
            ->with('question')
            ->get();

        if ($incorrectAnswers->isEmpty()) {
            return [];
        }

        // Group by question topic/category if available
        // Since we may not have explicit topics, we'll use question types as proxy
        $weakAreas = $incorrectAnswers->groupBy(function ($answer) {
            return $answer->question->question_type ?? 'unknown';
        })->map(function ($group, $type) use ($incorrectAnswers) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'percentage' => round($group->count() / $incorrectAnswers->count() * 100, 1),
            ];
        })->sortByDesc('count')->take(3)->values()->toArray();

        return $weakAreas;
    }

    /**
     * Generate recommendations based on risk factors
     */
    protected function generateRecommendations(float $riskScore, array $factors): array
    {
        $recommendations = [];

        // High risk general recommendation
        if ($riskScore >= StudentRiskPrediction::THRESHOLD_HIGH) {
            $recommendations[] = [
                'action' => 'urgent_intervention',
                'description' => 'Siswa membutuhkan perhatian segera dari guru',
                'priority' => 'high',
            ];
        }

        // Academic recommendations
        if (in_array('low_average', $factors['academic']['factors'])) {
            $recommendations[] = [
                'action' => 'assign_remedial',
                'description' => 'Berikan latihan tambahan untuk meningkatkan pemahaman',
                'priority' => 'high',
            ];
        }

        if (in_array('declining_trend', $factors['academic']['factors'])) {
            $recommendations[] = [
                'action' => 'review_recent_topics',
                'description' => 'Review materi terbaru yang mungkin belum dipahami',
                'priority' => 'medium',
            ];
        }

        // Behavioral recommendations
        if (in_array('high_violations', $factors['behavioral']['factors'])) {
            $recommendations[] = [
                'action' => 'discuss_behavior',
                'description' => 'Diskusikan pentingnya integritas akademik dengan siswa',
                'priority' => 'medium',
            ];
        }

        if (in_array('previously_flagged', $factors['behavioral']['factors'])) {
            $recommendations[] = [
                'action' => 'monitor_closely',
                'description' => 'Pantau siswa lebih ketat saat ujian',
                'priority' => 'medium',
            ];
        }

        // Engagement recommendations
        if (in_array('rushing_exams', $factors['engagement']['factors'])) {
            $recommendations[] = [
                'action' => 'time_management',
                'description' => 'Ajarkan teknik manajemen waktu saat ujian',
                'priority' => 'low',
            ];
        }

        // Contextual recommendations
        if (in_array('below_class_average', $factors['contextual']['factors'])) {
            $recommendations[] = [
                'action' => 'peer_tutoring',
                'description' => 'Pertimbangkan tutor teman sebaya',
                'priority' => 'medium',
            ];
        }

        // General encouragement for medium risk
        if ($riskScore >= StudentRiskPrediction::THRESHOLD_MEDIUM
            && $riskScore < StudentRiskPrediction::THRESHOLD_HIGH) {
            $recommendations[] = [
                'action' => 'send_encouragement',
                'description' => 'Kirim pesan motivasi kepada siswa',
                'priority' => 'low',
            ];
        }

        return $recommendations;
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Get class average for a specific lesson
     */
    protected function getClassAverageForLesson(int $classroomId, int $lessonId): ?float
    {
        return Cache::remember(
            "class_avg_{$classroomId}_{$lessonId}",
            self::CACHE_DURATION * 2,
            function () use ($classroomId, $lessonId) {
                return Grade::completed()
                    ->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId))
                    ->whereHas('exam', fn ($q) => $q->where('lesson_id', $lessonId))
                    ->avg('grade');
            }
        );
    }

    /**
     * Get student's average for a specific lesson
     */
    protected function getStudentLessonAverage(int $studentId, int $lessonId): ?float
    {
        return Cache::remember(
            "student_lesson_avg_{$studentId}_{$lessonId}",
            self::CACHE_DURATION,
            function () use ($studentId, $lessonId) {
                return Grade::completed()
                    ->where('student_id', $studentId)
                    ->whereHas('exam', fn ($q) => $q->where('lesson_id', $lessonId))
                    ->avg('grade');
            }
        );
    }

    /**
     * Calculate variance of an array
     */
    protected function calculateVariance(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $sumSquaredDiff = 0;

        foreach ($values as $value) {
            $sumSquaredDiff += pow($value - $mean, 2);
        }

        return $sumSquaredDiff / $count;
    }

    /**
     * Clear prediction cache for a student
     */
    public function clearCache(int $studentId, ?int $examId = null): void
    {
        $key = "risk_score_{$studentId}_".($examId ?? 'general');
        Cache::forget($key);
    }

    /**
     * Validate prediction after exam completion
     */
    public function validatePrediction(Grade $grade): void
    {
        $prediction = StudentRiskPrediction::where('student_id', $grade->student_id)
            ->where('exam_id', $grade->exam_id)
            ->whereNull('actual_score')
            ->first();

        if ($prediction) {
            $prediction->validatePrediction($grade->grade);

            Log::info('Prediction validated', [
                'prediction_id' => $prediction->id,
                'predicted' => $prediction->predicted_score,
                'actual' => $grade->grade,
                'error' => $prediction->prediction_error,
                'accurate' => $prediction->prediction_accurate,
            ]);
        }
    }

    /**
     * Get high risk students for an exam
     */
    public function getHighRiskStudents(int $examId): Collection
    {
        return StudentRiskPrediction::where('exam_id', $examId)
            ->highRisk()
            ->with('student')
            ->orderByDesc('risk_score')
            ->get();
    }

    /**
     * Get summary statistics for dashboard
     */
    public function getDashboardSummary(): array
    {
        $activePredictions = StudentRiskPrediction::active()->recent(7);

        return [
            'total_predictions' => $activePredictions->count(),
            'critical_count' => (clone $activePredictions)->critical()->count(),
            'high_risk_count' => (clone $activePredictions)->highRisk()->count(),
            'pending_interventions' => (clone $activePredictions)->pendingIntervention()->count(),
            'accuracy_rate' => $this->calculateAccuracyRate(),
        ];
    }

    /**
     * Calculate overall prediction accuracy rate
     */
    protected function calculateAccuracyRate(): ?float
    {
        $validated = StudentRiskPrediction::validated()->recent(30);
        $total = $validated->count();

        if ($total < 10) {
            return null; // Not enough data
        }

        $accurate = $validated->where('prediction_accurate', true)->count();

        return round($accurate / $total * 100, 1);
    }
}
