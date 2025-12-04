<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupReminder extends Command
{
    protected $signature = 'telegram:backup-reminder';
    protected $description = 'Send daily backup reminder with database stats';

    public function handle(TelegramService $telegram)
    {
        // Get database stats
        $stats = [
            'students' => DB::table('students')->count(),
            'exams' => DB::table('exams')->count(),
            'grades' => DB::table('grades')->count(),
            'violations' => DB::table('exam_violations')->count(),
        ];

        $dbSize = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size FROM information_schema.tables WHERE table_schema = ?", [config('database.connections.mysql.database')]);
        $size = $dbSize[0]->size ?? 'N/A';

        $message = "💾 <b>BACKUP REMINDER</b>\n\n"
            . "Jangan lupa backup database!\n\n"
            . "📊 <b>Database Stats:</b>\n"
            . "• Siswa: {$stats['students']}\n"
            . "• Ujian: {$stats['exams']}\n"
            . "• Hasil: {$stats['grades']}\n"
            . "• Violations: {$stats['violations']}\n"
            . "• Size: {$size} MB\n\n"
            . "🕐 " . now()->format('d/m/Y H:i');

        $telegram->sendToAll($message);
        $this->info('Backup reminder sent.');
    }
}
