<?php

namespace App\Listeners;

use App\Events\StudentBlocked;
use App\Jobs\SendTelegramNotification;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyStudentBlocked implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(StudentBlocked $event): void
    {
        $student = $event->student;

        // Log the block action
        ActivityLogService::log(
            type: 'student_blocked',
            description: "Student {$student->name} ({$student->nisn}) was blocked: {$event->reason}",
            loggableType: 'students',
            loggableId: $student->id,
            userId: auth()->id(),
            studentId: $student->id,
            ipAddress: request()->ip(),
            metadata: [
                'reason' => $event->reason,
                'blocked_at' => now()->toISOString(),
            ]
        );

        // Send Telegram notification if enabled
        if (config('services.telegram.enabled')) {
            $message = "🚫 *Siswa Diblokir*\n\n"
                ."👤 Nama: {$student->name}\n"
                ."🔢 NISN: {$student->nisn}\n"
                ."📋 Alasan: {$event->reason}\n"
                .'🕐 Waktu: '.now()->format('d/m/Y H:i:s');

            SendTelegramNotification::dispatch($message)->onQueue('notifications');
        }
    }
}
