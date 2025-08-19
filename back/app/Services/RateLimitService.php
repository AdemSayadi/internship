<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitService
{
    private const CACHE_PREFIX = 'groq_rate_limit';
    private const REQUESTS_PER_MINUTE = 30;
    private const REQUESTS_PER_HOUR = 1000;

    public function canMakeRequest(): bool
    {
        $minuteKey = self::CACHE_PREFIX . ':minute:' . now()->format('Y-m-d-H-i');
        $hourKey = self::CACHE_PREFIX . ':hour:' . now()->format('Y-m-d-H');

        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);

        return $minuteCount < self::REQUESTS_PER_MINUTE &&
            $hourCount < self::REQUESTS_PER_HOUR;
    }

    public function recordRequest(): void
    {
        $minuteKey = self::CACHE_PREFIX . ':minute:' . now()->format('Y-m-d-H-i');
        $hourKey = self::CACHE_PREFIX . ':hour:' . now()->format('Y-m-d-H');

        // Increment counters
        Cache::increment($minuteKey);
        Cache::increment($hourKey);

        // Set expiration
        Cache::put($minuteKey, Cache::get($minuteKey), now()->addMinutes(1));
        Cache::put($hourKey, Cache::get($hourKey), now()->addHour());
    }

    public function getRemainingRequests(): array
    {
        $minuteKey = self::CACHE_PREFIX . ':minute:' . now()->format('Y-m-d-H-i');
        $hourKey = self::CACHE_PREFIX . ':hour:' . now()->format('Y-m-d-H');

        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);

        return [
            'minute' => max(0, self::REQUESTS_PER_MINUTE - $minuteCount),
            'hour' => max(0, self::REQUESTS_PER_HOUR - $hourCount),
        ];
    }

    public function getWaitTime(): int
    {
        $remaining = $this->getRemainingRequests();

        if ($remaining['minute'] <= 0) {
            return 60; // Wait 1 minute
        }

        if ($remaining['hour'] <= 0) {
            return 3600; // Wait 1 hour
        }

        return 0;
    }

    public function logRateLimitStatus(): void
    {
        $remaining = $this->getRemainingRequests();

        Log::info('Groq API rate limit status', [
            'remaining_per_minute' => $remaining['minute'],
            'remaining_per_hour' => $remaining['hour'],
            'can_make_request' => $this->canMakeRequest()
        ]);
    }

    public function handleRateLimitExceeded(string $model, string $organization): int
    {
        $waitTime = $this->getWaitTime();

        Log::warning('Groq API rate limit exceeded', [
            'model' => $model,
            'organization' => $organization,
            'wait_time_seconds' => $waitTime
        ]);

        // Cache the rate limit state
        Cache::put(self::CACHE_PREFIX . ':blocked', true, now()->addSeconds($waitTime));

        return $waitTime;
    }

    public function isBlocked(): bool
    {
        return Cache::has(self::CACHE_PREFIX . ':blocked');
    }
}
