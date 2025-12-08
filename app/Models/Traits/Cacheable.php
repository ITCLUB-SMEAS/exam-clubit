<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    protected static function bootCacheable(): void
    {
        static::saved(fn($model) => $model->clearCache());
        static::deleted(fn($model) => $model->clearCache());
    }

    public function cacheKey(string $suffix = ''): string
    {
        return sprintf('%s:%s:%s', static::class, $this->getKey(), $suffix);
    }

    public function clearCache(): void
    {
        Cache::tags($this->getCacheTags())->flush();
    }

    protected function getCacheTags(): array
    {
        return [static::class, static::class . ':' . $this->getKey()];
    }

    public static function cacheQuery(string $key, int $ttl, callable $callback)
    {
        return Cache::tags([static::class])->remember($key, $ttl, $callback);
    }
}
