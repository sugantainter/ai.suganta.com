<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    private const SOFT_TTL = 14400; // 4 hours
    private const HARD_TTL = 604800; // 7 days

    protected $useRedis = null;

    /**
     * Check if Redis is available.
     */
    protected function isRedisAvailable(): bool
    {
        if ($this->useRedis !== null) {
            return $this->useRedis;
        }

        try {
            // Ping Redis to test connection
            Redis::ping();
            $this->useRedis = true;
        } catch (\Throwable $e) {
            $this->useRedis = false;
        }

        return $this->useRedis;
    }

    /**
     * Get HTML cache for a given slug.
     * Returns array with 'html' and 'is_stale' if found, otherwise null.
     */
    public function get(string $slug): ?array
    {
        $key = $this->getCacheKey($slug);

        try {
            if ($this->isRedisAvailable()) {
                $data = Redis::get($key);
            } else {
                $data = Cache::store('file')->get($key);
            }

            if (!$data) {
                return null;
            }

            $cached = json_decode($data, true);
            if (!is_array($cached) || !isset($cached['html'], $cached['cached_at'])) {
                return null;
            }

            $age = time() - $cached['cached_at'];
            $isStale = $age > self::SOFT_TTL;

            return [
                'html' => $cached['html'],
                'is_stale' => $isStale,
            ];
        } catch (\Throwable $e) {
            Log::warning("SEO Cache Read Failure: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Store HTML cache.
     */
    public function set(string $slug, string $html): void
    {
        $key = $this->getCacheKey($slug);
        
        $payload = json_encode([
            'html' => $html,
            'cached_at' => time()
        ]);

        try {
            if ($this->isRedisAvailable()) {
                Redis::setex($key, self::HARD_TTL, $payload);
            } else {
                Cache::store('file')->put($key, $payload, self::HARD_TTL);
            }
        } catch (\Throwable $e) {
            Log::warning("SEO Cache Write Failure: " . $e->getMessage());
        }
    }

    /**
     * Build cache key.
     */
    private function getCacheKey(string $slug): string
    {
        return 'seo_page:v4:' . md5(trim(strtolower($slug), '/'));
    }
}
