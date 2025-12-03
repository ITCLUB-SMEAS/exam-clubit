<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $examTitle,
        public float $score,
        public bool $passed
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->passed ? 'LULUS' : 'TIDAK LULUS';
        return [
            'type' => 'submitted',
            'icon' => 'fa-check-circle',
            'color' => $this->passed ? 'success' : 'warning',
            'title' => 'Ujian Selesai',
            'message' => "{$this->studentName} menyelesaikan ujian {$this->examTitle} dengan nilai {$this->score} ({$status})",
            'student_name' => $this->studentName,
            'exam_title' => $this->examTitle,
            'score' => $this->score,
            'passed' => $this->passed,
        ];
    }
}
