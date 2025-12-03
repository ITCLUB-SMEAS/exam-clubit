<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Grade;
use Illuminate\Support\Facades\Log;

class BehaviorAnalysisService
{
    // Minimum seconds expected to answer a question
    const MIN_ANSWER_TIME_SECONDS = 3;
    
    // Maximum suspicious fast answers before flagging
    const MAX_FAST_ANSWERS = 5;
    
    // Minimum seconds between answer submissions
    const MIN_SUBMISSION_INTERVAL = 1;

    /**
     * Analyze answer submission for suspicious behavior
     */
    public function analyzeAnswerSubmission(
        Grade $grade,
        int $questionId,
        ?string $previousAnswerTime = null
    ): array {
        $flags = [];
        $now = now();

        // Check for rapid-fire submissions
        if ($previousAnswerTime) {
            $interval = $now->diffInSeconds($previousAnswerTime);
            if ($interval < self::MIN_SUBMISSION_INTERVAL) {
                $flags[] = [
                    'type' => 'rapid_submission',
                    'message' => "Jawaban dikirim terlalu cepat ({$interval}s interval)",
                    'severity' => 'warning',
                ];
            }
        }

        return $flags;
    }

    /**
     * Analyze exam completion for suspicious patterns
     */
    public function analyzeExamCompletion(Grade $grade): array
    {
        $flags = [];
        
        $answers = Answer::where('exam_id', $grade->exam_id)
            ->where('exam_session_id', $grade->exam_session_id)
            ->where('student_id', $grade->student_id)
            ->whereNotNull('updated_at')
            ->orderBy('updated_at')
            ->get();

        if ($answers->isEmpty()) {
            return $flags;
        }

        // Check for suspiciously fast completion
        $fastAnswers = $this->countFastAnswers($answers);
        if ($fastAnswers >= self::MAX_FAST_ANSWERS) {
            $flags[] = [
                'type' => 'fast_completion',
                'message' => "{$fastAnswers} jawaban dijawab sangat cepat",
                'severity' => 'high',
            ];
        }

        // Check for answer pattern (all same answer)
        $sameAnswerPattern = $this->detectSameAnswerPattern($answers);
        if ($sameAnswerPattern) {
            $flags[] = [
                'type' => 'same_answer_pattern',
                'message' => "Pola jawaban mencurigakan terdeteksi",
                'severity' => 'medium',
            ];
        }

        // Check for perfect score with fast time
        if ($grade->grade == 100 && $this->wasCompletedFast($grade)) {
            $flags[] = [
                'type' => 'perfect_fast',
                'message' => "Nilai sempurna dengan waktu sangat cepat",
                'severity' => 'medium',
            ];
        }

        // Log and flag if suspicious
        if (!empty($flags)) {
            $this->logSuspiciousActivity($grade, $flags);
            $this->updateGradeFlags($grade, $flags);
        }

        return $flags;
    }

    /**
     * Count answers that were submitted too quickly
     */
    protected function countFastAnswers($answers): int
    {
        $fastCount = 0;
        $previousTime = null;

        foreach ($answers as $answer) {
            $currentTime = $answer->updated_at instanceof \Carbon\Carbon 
                ? $answer->updated_at 
                : \Carbon\Carbon::parse($answer->updated_at);
                
            if ($previousTime && $currentTime) {
                $interval = abs($previousTime->diffInSeconds($currentTime));
                if ($interval < self::MIN_ANSWER_TIME_SECONDS) {
                    $fastCount++;
                }
            }
            $previousTime = $currentTime;
        }

        return $fastCount;
    }

    /**
     * Detect if student answered all questions with same answer
     */
    protected function detectSameAnswerPattern($answers): bool
    {
        $multipleChoiceAnswers = $answers->filter(fn($a) => $a->answer > 0);
        
        if ($multipleChoiceAnswers->count() < 5) {
            return false;
        }

        $answerCounts = $multipleChoiceAnswers->groupBy('answer');
        $maxSameAnswer = $answerCounts->max(fn($group) => $group->count());
        
        // If more than 80% answers are the same, flag it
        return ($maxSameAnswer / $multipleChoiceAnswers->count()) > 0.8;
    }

    /**
     * Check if exam was completed unusually fast
     */
    protected function wasCompletedFast(Grade $grade): bool
    {
        if (!$grade->start_time || !$grade->end_time) {
            return false;
        }

        $durationMinutes = $grade->start_time->diffInMinutes($grade->end_time);
        $examDuration = $grade->exam->duration ?? 60;
        
        // If completed in less than 20% of allotted time
        return $durationMinutes < ($examDuration * 0.2);
    }

    /**
     * Log suspicious activity
     */
    protected function logSuspiciousActivity(Grade $grade, array $flags): void
    {
        Log::channel('daily')->warning('Suspicious exam behavior detected', [
            'student_id' => $grade->student_id,
            'exam_id' => $grade->exam_id,
            'grade_id' => $grade->id,
            'flags' => $flags,
        ]);

        ActivityLogService::log(
            action: 'suspicious_behavior',
            module: 'anticheat',
            description: 'Perilaku mencurigakan terdeteksi: ' . collect($flags)->pluck('type')->implode(', '),
            subject: $grade,
            metadata: ['flags' => $flags]
        );
    }

    /**
     * Update grade with behavior flags
     */
    protected function updateGradeFlags(Grade $grade, array $flags): void
    {
        $highSeverity = collect($flags)->contains('severity', 'high');
        
        if ($highSeverity && !$grade->is_flagged) {
            $grade->update([
                'is_flagged' => true,
                'flag_reason' => 'Behavior analysis: ' . collect($flags)->pluck('type')->implode(', '),
            ]);
        }
    }

    /**
     * Get behavior analysis summary for a grade
     */
    public static function getSummary(Grade $grade): array
    {
        $service = new self();
        return $service->analyzeExamCompletion($grade);
    }
}
