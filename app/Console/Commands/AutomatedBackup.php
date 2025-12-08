<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AutomatedBackup extends Command
{
    protected $signature = 'backup:automated {--verify : Verify backup after creation}';
    protected $description = 'Create automated database backup with verification';

    public function handle()
    {
        $this->info('Starting automated backup...');

        $filename = 'backup-' . now()->format('Y-m-d_His') . '.sql';
        $path = storage_path('app/backups/' . $filename);

        // Create backups directory if not exists
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Get database credentials
        $dbHost = config('database.connections.mysql.host');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Create backup
        $command = sprintf(
            'mysqldump -h%s -u%s %s %s > %s 2>&1',
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $dbPass ? '-p' . escapeshellarg($dbPass) : '',
            escapeshellarg($dbName),
            escapeshellarg($path)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->error('Backup failed!');
            $this->sendTelegramAlert('❌ Backup GAGAL: ' . implode("\n", $output));
            return 1;
        }

        $fileSize = filesize($path);
        $this->info("Backup created: {$filename} (" . $this->formatBytes($fileSize) . ")");

        // Verify backup if requested
        if ($this->option('verify')) {
            if (!$this->verifyBackup($path)) {
                $this->error('Backup verification failed!');
                $this->sendTelegramAlert('⚠️ Backup dibuat tapi VERIFIKASI GAGAL');
                return 1;
            }
            $this->info('Backup verified successfully!');
        }

        // Cleanup old backups (keep last 7 days)
        $this->cleanupOldBackups();

        $this->info('Backup completed successfully!');
        $this->sendTelegramAlert("✅ Backup berhasil: {$filename} (" . $this->formatBytes($fileSize) . ")");

        return 0;
    }

    protected function verifyBackup(string $path): bool
    {
        // Check file exists and not empty
        if (!file_exists($path) || filesize($path) < 1000) {
            return false;
        }

        // Check file contains SQL dump markers
        $content = file_get_contents($path, false, null, 0, 2000);
        $validMarkers = [
            'MySQL dump',
            'MariaDB dump',
            'CREATE TABLE',
            'Database:',
            'Server version'
        ];

        foreach ($validMarkers as $marker) {
            if (str_contains($content, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function cleanupOldBackups(): void
    {
        $backupDir = storage_path('app/backups');
        $files = glob($backupDir . '/backup-*.sql');
        $cutoffTime = now()->subDays(7)->timestamp;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $this->info('Deleted old backup: ' . basename($file));
            }
        }
    }

    protected function sendTelegramAlert(string $message): void
    {
        try {
            app(TelegramService::class)->sendMessage($message);
        } catch (\Exception $e) {
            $this->warn('Failed to send Telegram notification: ' . $e->getMessage());
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
