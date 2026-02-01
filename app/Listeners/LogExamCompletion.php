<?php

namespace App\Listeners;

use App\Events\ExamCompleted;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogExamCompletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExamCompleted $event): void
    {
        $grade = $event->grade;
        $grade->load(['student', 'exam']);

        $method = $event->autoSubmitted ? 'auto-submitted' : 'submitted';

        ActivityLogService::log(
            type: 'exam_completed',
            description: "Student {$grade->student->name} completed exam {$grade->exam->title} ({$method}) with grade {$grade->grade}",
            loggableType: 'grades',
            loggableId: $grade->id,
            userId: null,
            studentId: $grade->student_id,
            ipAddress: null,
            metadata: [
                'grade' => $grade->grade,
                'status' => $grade->status,
                'auto_submitted' => $event->autoSubmitted,
                'violation_count' => $grade->violation_count,
            ]
        );
    }
}
