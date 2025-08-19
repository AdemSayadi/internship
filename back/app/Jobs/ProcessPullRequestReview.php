<?php

namespace App\Jobs;

use App\Models\PullRequest;
use App\Services\AICodeReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessPullRequestReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $maxExceptions = 3;
    public int $timeout = 300; // 5 minutes
    public int $backoff = 300; // 5 minutes between retries

    protected PullRequest $pullRequest;

    public function __construct(PullRequest $pullRequest)
    {
        $this->pullRequest = $pullRequest;
    }

    public function handle(AICodeReviewService $aiService): void
    {
        try {
            Log::info('Starting AI review for pull request', [
                'pr_id' => $this->pullRequest->id,
                'github_pr_number' => $this->pullRequest->github_pr_number
            ]);

            $review = $aiService->reviewPullRequest($this->pullRequest);

            Log::info('AI review completed successfully', [
                'pr_id' => $this->pullRequest->id,
                'review_id' => $review->id,
                'score' => $review->score
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process pull request review', [
                'pr_id' => $this->pullRequest->id,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            // If it's a rate limit error, release the job back to queue with delay
            if ($this->isRateLimitError($e)) {
                $this->release(600); // Release for 10 minutes
                return;
            }

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Pull request review permanently failed', [
            'pr_id' => $this->pullRequest->id,
            'error' => $exception->getMessage()
        ]);

        // Optionally update the pull request status or notify someone
        // You might want to create a failed review record here
    }

    private function isRateLimitError(Exception $e): bool
    {
        return $e->getCode() === 429 ||
            str_contains($e->getMessage(), 'Rate limit') ||
            str_contains($e->getMessage(), '429');
    }

    // Calculate backoff time dynamically based on attempt number
    public function backoff(): array
    {
        return [
            300,  // 5 minutes for first retry
            900,  // 15 minutes for second retry
            1800, // 30 minutes for third retry
        ];
    }
}
