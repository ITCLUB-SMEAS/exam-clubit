<?php

namespace App\Listeners;

use App\Events\ViolationRecorded;
use App\Jobs\SendTelegramNotification;
use App\Models\Student;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleViolation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(ViolationRecorded $event): void
    {
        $violation = $event->violation;
        $totalViolations = $event->totalViolations;

        $violation->load(['student', 'exam', 'grade']);

        // Check if auto-block threshold is reached
        $autoBlockThreshold = config('anticheat.auto_block_threshold', 3);

        if ($totalViolations >= $autoBlockThreshold) {
            $this->autoBlockStudent($violation->student, $violation->exam->title, $totalViolations);
        }

        // Send Telegram notification if configured
        if (config('services.telegram.enabled') && config('anticheat.telegram_notify', true)) {
            $this->sendTelegramAlert($violation, $totalViolations);
        }
    }

    /**
     * Auto-block student after reaching threshold.
     */
    private function autoBlockStudent(Student $student, string $examTitle, int $violationCount): void
    {
        if ($student->is_blocked) {
            return; // Already blocked
        }

        $reason = "Auto-blocked: {$violationCount} violations during exam '{$examTitle}'";

        $student->block($reason);
    }

    /**
     * Send Telegram notification about violation.
     */
    private function sendTelegramAlert($violation, int $totalViolations): void
    {
        $message = "⚠️ *Pelanggaran Ujian*\n\n"
            ."👤 Siswa: {$violation->student->name}\n"
            ."📝 Ujian: {$violation->exam->title}\n"
            ."🚨 Jenis: {$violation->violation_type}\n"
            ."📊 Total Pelanggaran: {$totalViolations}\n"
            .'🕐 Waktu: '.now()->format('d/m/Y H:i:s');

        SendTelegramNotification::dispatch($message)->onQueue('notifications');
    }
}
