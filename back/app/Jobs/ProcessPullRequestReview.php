<?php

namespace App\Jobs;

use App\Models\PullRequest;
use App\Models\PullRequestReview;
use App\Services\PullRequestReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPullRequestReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected PullRequest $pullRequest;
    protected PullRequestReview $review;

    /**
     * Create a new job instance.
     */
    public function __construct(PullRequest $pullRequest, PullRequestReview $review)
    {
        $this->pullRequest = $pullRequest;
        $this->review = $review;
    }

    /**
     * Execute the job.
     */
    public function handle(PullRequestReviewService $reviewService): void
    {
        try {
            Log::info('Processing PR review job', [
                'pull_request_id' => $this->pullRequest->id,
                'review_id' => $this->review->id
            ]);

            $reviewService->processReview($this->pullRequest, $this->review);

        } catch (\Exception $e) {
            Log::error('PR review job failed', [
                'pull_request_id' => $this->pullRequest->id,
                'review_id' => $this->review->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update review status to failed
            $this->review->update([
                'status' => 'failed',
                'summary' => 'Review processing failed: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('PR review job permanently failed', [
            'pull_request_id' => $this->pullRequest->id,
            'review_id' => $this->review->id,
            'error' => $exception->getMessage()
        ]);

        // Mark review as failed
        $this->review->update([
            'status' => 'failed',
            'summary' => 'Review processing permanently failed: ' . $exception->getMessage(),
        ]);
    }
}
