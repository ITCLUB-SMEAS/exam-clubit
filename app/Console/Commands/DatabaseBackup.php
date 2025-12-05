<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database {--cleanup}';
    protected $description = 'Create database backup and optionally cleanup old backups';

    public function handle(BackupService $backup, TelegramService $telegram): int
    {
        $this->info('Creating database backup...');

        $path = $backup->createDatabaseBackup();

        if (!$path) {
            $this->error('Backup failed!');
            $telegram->sendMessage("❌ *Database Backup Gagal*\n\nWaktu: " . now()->format('d/m/Y H:i:s'));
            return 1;
        }

        $this->info("Backup created: {$path}");

        if ($this->option('cleanup')) {
            $deleted = $backup->cleanOldBackups(7);
            $this->info("Cleaned up {$deleted} old backups.");
        }

        $telegram->sendMessage("✅ *Database Backup Berhasil*\n\nFile: `" . basename($path) . "`\nWaktu: " . now()->format('d/m/Y H:i:s'));

        return 0;
    }
}
