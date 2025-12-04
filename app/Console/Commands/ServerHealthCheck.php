<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ServerHealthCheck extends Command
{
    protected $signature = 'server:health-check';
    protected $description = 'Check server health and alert via Telegram if issues found';

    public function handle(TelegramService $telegram)
    {
        $issues = [];
        $metrics = [];

        // 1. Database connection
        $dbStart = microtime(true);
        try {
            DB::select('SELECT 1');
            $metrics['db_ms'] = round((microtime(true) - $dbStart) * 1000);
            if ($metrics['db_ms'] > 1000) {
                $issues[] = "⚠️ Database lambat: {$metrics['db_ms']}ms";
            }
        } catch (\Exception $e) {
            $issues[] = "🔴 Database ERROR: " . $e->getMessage();
        }

        // 2. Disk space
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsedPercent = round((1 - $diskFree / $diskTotal) * 100);
        $metrics['disk_percent'] = $diskUsedPercent;
        if ($diskUsedPercent > 90) {
            $issues[] = "🔴 Disk hampir penuh: {$diskUsedPercent}%";
        } elseif ($diskUsedPercent > 80) {
            $issues[] = "⚠️ Disk usage tinggi: {$diskUsedPercent}%";
        }

        // 3. Web response time
        $webStart = microtime(true);
        try {
            $response = Http::timeout(10)->get(config('app.url'));
            $metrics['web_ms'] = round((microtime(true) - $webStart) * 1000);
            if (!$response->successful()) {
                $issues[] = "🔴 Web ERROR: HTTP {$response->status()}";
            } elseif ($metrics['web_ms'] > 3000) {
                $issues[] = "⚠️ Web lambat: {$metrics['web_ms']}ms";
            }
        } catch (\Exception $e) {
            $issues[] = "🔴 Web tidak bisa diakses";
        }

        // 4. Queue check (if using database queue)
        $failedJobs = DB::table('failed_jobs')->count();
        if ($failedJobs > 0) {
            $issues[] = "⚠️ Failed jobs: {$failedJobs}";
        }

        // 5. Storage writable
        if (!is_writable(storage_path('logs'))) {
            $issues[] = "🔴 Storage tidak writable";
        }

        // Send alert if issues found
        if (!empty($issues)) {
            // Prevent spam - only alert once per hour for same issues
            $issueHash = md5(implode('', $issues));
            $cacheKey = "health_alert_{$issueHash}";
            
            if (!Cache::has($cacheKey)) {
                $message = "🏥 <b>SERVER HEALTH ALERT</b>\n\n"
                    . implode("\n", $issues) . "\n\n"
                    . "📊 <b>Metrics:</b>\n"
                    . "• DB: " . ($metrics['db_ms'] ?? 'N/A') . "ms\n"
                    . "• Web: " . ($metrics['web_ms'] ?? 'N/A') . "ms\n"
                    . "• Disk: " . ($metrics['disk_percent'] ?? 'N/A') . "%\n"
                    . "🕐 " . now()->format('d/m/Y H:i:s');

                $telegram->sendToAll($message);
                Cache::put($cacheKey, true, now()->addHour());
                
                $this->error('Issues found and alert sent!');
            } else {
                $this->warn('Issues found but alert already sent recently.');
            }
        } else {
            $this->info('All systems healthy.');
        }

        return 0;
    }
}
