<?php

namespace App\Console\Commands;

use App\Services\RateLimitService;
use Illuminate\Console\Command;

class CheckGroqRateLimit extends Command
{
    protected $signature = 'groq:rate-limit {--reset : Reset the rate limit counters}';
    protected $description = 'Check or reset Groq API rate limit status';

    public function handle(RateLimitService $rateLimitService): int
    {
        if ($this->option('reset')) {
            $this->resetRateLimits();
            $this->info('Rate limit counters have been reset.');
            return 0;
        }

        $remaining = $rateLimitService->getRemainingRequests();
        $canMakeRequest = $rateLimitService->canMakeRequest();
        $isBlocked = $rateLimitService->isBlocked();

        $this->info('Groq API Rate Limit Status:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Remaining requests (this minute)', $remaining['minute']],
                ['Remaining requests (this hour)', $remaining['hour']],
                ['Can make request', $canMakeRequest ? 'Yes' : 'No'],
                ['Currently blocked', $isBlocked ? 'Yes' : 'No'],
            ]
        );

        if (!$canMakeRequest) {
            $waitTime = $rateLimitService->getWaitTime();
            $this->warn("Rate limit exceeded. Wait time: {$waitTime} seconds");
        }

        return 0;
    }

    private function resetRateLimits(): void
    {
        $keys = [
            'groq_rate_limit:minute:' . now()->format('Y-m-d-H-i'),
            'groq_rate_limit:hour:' . now()->format('Y-m-d-H'),
            'groq_rate_limit:blocked',
        ];

        foreach ($keys as $key) {
            cache()->forget($key);
        }
    }
}
