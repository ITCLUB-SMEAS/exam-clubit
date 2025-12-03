<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamStartedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $examTitle
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'started',
            'icon' => 'fa-play-circle',
            'color' => 'info',
            'title' => 'Ujian Dimulai',
            'message' => "{$this->studentName} memulai ujian {$this->examTitle}",
            'student_name' => $this->studentName,
            'exam_title' => $this->examTitle,
        ];
    }
}
