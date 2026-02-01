<?php

namespace App\Services;

use App\Events\ExamCompleted;
use App\Models\Answer;
use App\Models\ExamGroup;
use App\Models\Grade;
use App\Models\Question;
use App\Models\User;
use App\Notifications\ExamSubmittedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExamCompletionService
{
    public function __construct(
        protected BehaviorAnalysisService $behaviorService
    ) {}

    /**
     * Finalize exam and calculate final grade
     */
    public function finalizeExam(ExamGroup $examGroup, Grade $grade, int $studentId): void
    {
        if ($grade->end_time !== null) {
            return;
        }

        DB::transaction(function () use ($examGroup, $grade, $studentId) {
            $questions = Question::where('exam_id', $examGroup->exam_id)->get();
            $answers = Answer::where('exam_id', $examGroup->exam_id)
                ->where('exam_session_id', $examGroup->exam_session_id)
                ->where('student_id', $studentId)
                ->get();

            $totalPoints = $questions->sum(fn ($q) => $q->points ?? 1);
            $earnedPoints = $answers->sum(fn ($a) => $a->points_awarded ?? 0);
            $correctCount = $answers->where('is_correct', 'Y')->count();

            $gradeValue = $totalPoints > 0
                ? round(($earnedPoints / $totalPoints) * 100, 2)
                : 0;

            $passingGrade = $examGroup->exam->passing_grade ?? 0;
            $status = $passingGrade > 0
                ? ($gradeValue >= $passingGrade ? 'passed' : 'failed')
                : 'pending';

            $grade->update([
                'end_time' => Carbon::now(),
                'duration' => 0,
                'total_correct' => $correctCount,
                'grade' => $gradeValue,
                'points_possible' => $totalPoints,
                'points_earned' => $earnedPoints,
                'attempt_status' => 'completed',
                'status' => $status,
            ]);

            // Run behavior analysis after completion
            $grade->load('exam');
            $this->behaviorService->analyzeExamCompletion($grade);

            // Log exam end
            if (auth()->guard('student')->check()) {
                $student = auth()->guard('student')->user();
                ActivityLogService::logExamEnd($student, $examGroup->exam, $grade);

                // Notify admins
                $this->notifyAdmins(
                    $student->name,
                    $examGroup->exam->title,
                    $gradeValue,
                    $status === 'passed'
                );
            }

            // Dispatch ExamCompleted event for additional processing
            ExamCompleted::dispatch($grade, false);
        });
    }

    /**
     * Notify admins when exam is submitted
     */
    protected function notifyAdmins(string $studentName, string $examTitle, float $score, bool $passed): void
    {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new ExamSubmittedNotification($studentName, $examTitle, $score, $passed));
        }
    }

    /**
     * Check if exam is already completed
     */
    public function isCompleted(Grade $grade): bool
    {
        return $grade->end_time !== null || $grade->attempt_status === 'completed';
    }
}
