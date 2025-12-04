<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class ExamStartingAlert extends Command
{
    protected $signature = 'telegram:exam-starting-alert';
    protected $description = 'Send alert for exams starting in 15 minutes';

    public function handle(TelegramService $telegram)
    {
        $sessions = ExamSession::with('exam')
            ->whereBetween('start_time', [now()->addMinutes(14), now()->addMinutes(16)])
            ->get();

        foreach ($sessions as $session) {
            $enrolledCount = $session->examGroups()->count();
            $exam = $session->exam;

            $message = "⏰ <b>UJIAN DIMULAI 15 MENIT LAGI</b>\n\n"
                . "📝 {$exam->title}\n"
                . "👥 Peserta: {$enrolledCount} siswa\n"
                . "⏱️ Durasi: {$exam->duration} menit\n"
                . "🕐 Mulai: {$session->start_time->format('H:i')}";

            $telegram->sendToAll($message);
            $this->info("Alert sent for: {$exam->title}");
        }

        if ($sessions->isEmpty()) {
            $this->info('No exams starting in 15 minutes.');
        }
    }
}
