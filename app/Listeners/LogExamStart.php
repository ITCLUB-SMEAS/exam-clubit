<?php

namespace App\Listeners;

use App\Events\ExamStarted;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogExamStart implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ExamStarted $event): void
    {
        $grade = $event->grade;
        $grade->load(['student', 'exam', 'exam_session']);

        ActivityLogService::log(
            type: 'exam_started',
            description: "Student {$grade->student->name} started exam {$grade->exam->title}",
            loggableType: 'grades',
            loggableId: $grade->id,
            userId: null,
            studentId: $grade->student_id,
            ipAddress: request()->ip(),
            metadata: [
                'exam_id' => $grade->exam_id,
                'session_id' => $grade->exam_session_id,
                'start_time' => $grade->start_time?->toISOString(),
            ]
        );
    }
}
