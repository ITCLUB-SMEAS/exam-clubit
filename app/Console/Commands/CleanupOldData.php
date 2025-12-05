<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ExamViolation;
use App\Models\LoginHistory;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldData extends Command
{
    protected $signature = 'cleanup:old-data {--days=90 : Days to keep data} {--dry-run : Show what would be deleted}';
    protected $description = 'Cleanup old activity logs, violations, login history, and snapshots';

    public function handle(TelegramService $telegram): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $this->info("Cleaning up data older than {$days} days ({$cutoff->format('Y-m-d')})...");
        if ($dryRun) {
            $this->warn('DRY RUN - No data will be deleted');
        }
        $this->newLine();

        $results = [];

        // 1. Activity Logs
        $activityCount = ActivityLog::where('created_at', '<', $cutoff)->count();
        $results['Activity Logs'] = $activityCount;
        if (!$dryRun && $activityCount > 0) {
            ActivityLog::where('created_at', '<', $cutoff)->delete();
        }
        $this->info("Activity Logs: {$activityCount} records");

        // 2. Login History
        $loginCount = LoginHistory::where('created_at', '<', $cutoff)->count();
        $results['Login History'] = $loginCount;
        if (!$dryRun && $loginCount > 0) {
            LoginHistory::where('created_at', '<', $cutoff)->delete();
        }
        $this->info("Login History: {$loginCount} records");

        // 3. Violation Snapshots (files)
        $snapshotCount = 0;
        $violations = ExamViolation::where('created_at', '<', $cutoff)
            ->whereNotNull('snapshot_path')
            ->get();
        
        foreach ($violations as $v) {
            if ($v->snapshot_path && Storage::disk('local')->exists($v->snapshot_path)) {
                $snapshotCount++;
                if (!$dryRun) {
                    Storage::disk('local')->delete($v->snapshot_path);
                }
            }
        }
        $results['Violation Snapshots'] = $snapshotCount;
        $this->info("Violation Snapshots: {$snapshotCount} files");

        // 4. Old Violations (keep snapshot_path null after file deleted)
        $violationCount = ExamViolation::where('created_at', '<', $cutoff)->count();
        $results['Exam Violations'] = $violationCount;
        if (!$dryRun && $violationCount > 0) {
            ExamViolation::where('created_at', '<', $cutoff)->delete();
        }
        $this->info("Exam Violations: {$violationCount} records");

        // 5. Old Backups
        $backupCount = 0;
        $backupFiles = Storage::disk('local')->files('backups');
        foreach ($backupFiles as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);
            if ($lastModified < $cutoff->timestamp) {
                $backupCount++;
                if (!$dryRun) {
                    Storage::disk('local')->delete($file);
                }
            }
        }
        $results['Old Backups'] = $backupCount;
        $this->info("Old Backups: {$backupCount} files");

        $this->newLine();
        
        $total = array_sum($results);
        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$total} items total");
        } else {
            $this->info("✅ Cleanup completed! Deleted {$total} items total");
            
            // Send Telegram notification
            $telegram->sendMessage(
                "🧹 <b>Auto Cleanup Completed</b>\n\n" .
                "📅 Data older than: {$days} days\n" .
                "📊 Activity Logs: {$results['Activity Logs']}\n" .
                "🔐 Login History: {$results['Login History']}\n" .
                "📷 Snapshots: {$results['Violation Snapshots']}\n" .
                "⚠️ Violations: {$results['Exam Violations']}\n" .
                "💾 Backups: {$results['Old Backups']}\n" .
                "━━━━━━━━━━━━━━━\n" .
                "Total: {$total} items deleted"
            );
        }

        return 0;
    }
}
