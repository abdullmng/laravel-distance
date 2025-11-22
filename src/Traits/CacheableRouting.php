<?php

namespace Abdullmng\Distance\Traits;

trait CacheableRouting
{
    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return config('distance.cache.enabled', true);
    }

    /**
     * Get cache duration in minutes.
     *
     * @return int
     */
    protected function getCacheDuration(): int
    {
        return config('distance.cache.duration', 1440);
    }

    /**
     * Generate cache key for routing.
     *
     * @param string $type
     * @param string $identifier
     * @return string
     */
    protected function getCacheKey(string $type, string $identifier): string
    {
        $prefix = config('distance.cache.prefix', 'routing');
        return "{$prefix}:{$type}:" . md5($identifier);
    }
}

