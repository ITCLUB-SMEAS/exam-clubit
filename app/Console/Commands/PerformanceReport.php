<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;

class PerformanceReport extends Command
{
    protected $signature = 'performance:report';
    protected $description = 'Send performance metrics to Telegram';

    public function handle(TelegramService $telegram)
    {
        $metrics = $this->collectMetrics();
        $message = $this->formatMessage($metrics);
        
        $telegram->sendMessage($message);
        
        $this->info('Performance report sent to Telegram');
        return 0;
    }

    private function collectMetrics(): array
    {
        return [
            'database' => $this->getDatabaseMetrics(),
            'cache' => $this->getCacheMetrics(),
            'storage' => $this->getStorageMetrics(),
            'queue' => $this->getQueueMetrics(),
            'memory' => $this->getMemoryMetrics(),
        ];
    }

    private function getDatabaseMetrics(): array
    {
        $start = microtime(true);
        DB::connection()->getPdo();
        $connectionTime = round((microtime(true) - $start) * 1000, 2);

        $tableSize = DB::select("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ", [config('database.connections.mysql.database')]);

        return [
            'connection_time' => $connectionTime . 'ms',
            'size' => ($tableSize[0]->size_mb ?? 0) . ' MB',
            'active_connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 'N/A',
        ];
    }

    private function getCacheMetrics(): array
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $info = $redis->info('memory');
            
            return [
                'status' => 'connected',
                'memory' => round($info['used_memory'] / 1024 / 1024, 2) . ' MB',
                'keys' => $redis->dbsize(),
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getStorageMetrics(): array
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $usedPercent = round(($used / $total) * 100, 2);

        return [
            'total' => round($total / 1024 / 1024 / 1024, 2) . ' GB',
            'used' => round($used / 1024 / 1024 / 1024, 2) . ' GB',
            'free' => round($free / 1024 / 1024 / 1024, 2) . ' GB',
            'used_percent' => $usedPercent . '%',
        ];
    }

    private function getQueueMetrics(): array
    {
        $pending = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        return [
            'pending' => $pending,
            'failed' => $failed,
        ];
    }

    private function getMemoryMetrics(): array
    {
        return [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function formatMessage(array $metrics): string
    {
        $emoji = $this->getHealthEmoji($metrics);
        
        return "📊 *Performance Report* {$emoji}\n\n" .
               "🗄️ *Database*\n" .
               "├ Connection: {$metrics['database']['connection_time']}\n" .
               "├ Size: {$metrics['database']['size']}\n" .
               "└ Connections: {$metrics['database']['active_connections']}\n\n" .
               
               "⚡ *Cache (Redis)*\n" .
               "├ Status: {$metrics['cache']['status']}\n" .
               "├ Memory: {$metrics['cache']['memory']}\n" .
               "└ Keys: {$metrics['cache']['keys']}\n\n" .
               
               "💾 *Storage*\n" .
               "├ Total: {$metrics['storage']['total']}\n" .
               "├ Used: {$metrics['storage']['used']} ({$metrics['storage']['used_percent']})\n" .
               "└ Free: {$metrics['storage']['free']}\n\n" .
               
               "📋 *Queue*\n" .
               "├ Pending: {$metrics['queue']['pending']}\n" .
               "└ Failed: {$metrics['queue']['failed']}\n\n" .
               
               "🧠 *Memory*\n" .
               "├ Current: {$metrics['memory']['current']}\n" .
               "├ Peak: {$metrics['memory']['peak']}\n" .
               "└ Limit: {$metrics['memory']['limit']}\n\n" .
               
               "_Report generated at " . now()->format('Y-m-d H:i:s') . "_";
    }

    private function getHealthEmoji(array $metrics): string
    {
        $storageUsed = (float) str_replace('%', '', $metrics['storage']['used_percent']);
        $failedJobs = $metrics['queue']['failed'];

        if ($storageUsed > 90 || $failedJobs > 10) {
            return '🔴';
        } elseif ($storageUsed > 75 || $failedJobs > 5) {
            return '🟡';
        }
        return '🟢';
    }
}
