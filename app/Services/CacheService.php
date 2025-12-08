<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_SHORT = 60;      // 1 minute
    const TTL_MEDIUM = 300;    // 5 minutes
    const TTL_LONG = 3600;     // 1 hour
    const TTL_DAY = 86400;     // 24 hours

    /**
     * Cache with automatic invalidation
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Invalidate cache by pattern
     */
    public static function forget(string $pattern): void
    {
        if (str_contains($pattern, '*')) {
            // Pattern matching (requires Redis)
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            Cache::forget($pattern);
        }
    }

    /**
     * Cache tags for grouped invalidation
     */
    public static function tags(array $tags)
    {
        return Cache::tags($tags);
    }

    /**
     * Flush all cache
     */
    public static function flush(): void
    {
        Cache::flush();
    }
}
