<?php

namespace App\Services;

use Illuminate\Support\Facades\{Cache, DB, Redis};

class PerformanceMonitorService
{
    public function getMetrics(): array
    {
        return [
            'cache' => $this->getCacheMetrics(),
            'database' => $this->getDatabaseMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'opcache' => $this->getOpcacheMetrics(),
        ];
    }

    private function getCacheMetrics(): array
    {
        try {
            $redis = Redis::connection();
            $info = $redis->info();
            
            return [
                'hit_rate' => $this->calculateHitRate($info),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'keys' => $redis->dbsize(),
            ];
        } catch (\Exception $e) {
            return ['status' => 'unavailable'];
        }
    }

    private function getDatabaseMetrics(): array
    {
        $queries = DB::getQueryLog();
        
        return [
            'total_queries' => count($queries),
            'slow_queries' => collect($queries)->filter(fn($q) => $q['time'] > 100)->count(),
            'connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
        ];
    }

    private function getMemoryMetrics(): array
    {
        return [
            'usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getOpcacheMetrics(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['status' => 'disabled'];
        }

        $status = opcache_get_status();
        
        return [
            'enabled' => $status !== false,
            'hit_rate' => round($status['opcache_statistics']['opcache_hit_rate'] ?? 0, 2),
            'memory_usage' => round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . ' MB',
        ];
    }

    private function calculateHitRate(array $info): float
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }
}
