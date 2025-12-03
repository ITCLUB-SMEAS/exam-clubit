<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ExamViolationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $studentName,
        public string $examTitle,
        public string $violationType,
        public int $totalViolations
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'violation',
            'icon' => 'fa-shield-alt',
            'color' => 'danger',
            'title' => 'Pelanggaran Terdeteksi',
            'message' => "{$this->studentName} melakukan pelanggaran ({$this->violationType}) pada ujian {$this->examTitle}. Total: {$this->totalViolations}",
            'student_name' => $this->studentName,
            'exam_title' => $this->examTitle,
            'violation_type' => $this->violationType,
        ];
    }
}
