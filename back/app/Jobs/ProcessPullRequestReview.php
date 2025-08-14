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

    protected PullRequest $pullRequest;

    public $timeout = 600; // 10 minutes timeout for PR reviews (multiple files)
    public $tries = 3;

    public function __construct(PullRequest $pullRequest)
    {
        $this->pullRequest = $pullRequest;
    }

    public function handle(AICodeReviewService $reviewService): void
    {
        try {
            Log::info('Starting AI review for pull request', [
                'pr_id' => $this->pullRequest->id,
                'github_pr_number' => $this->pullRequest->github_pr_number
            ]);

            $review = $reviewService->reviewPullRequest($this->pullRequest);

            Log::info('AI review completed for pull request', [
                'pr_id' => $this->pullRequest->id,
                'review_id' => $review->id,
                'score' => $review->score
            ]);

            // You can add GitHub comment here if needed
            // $this->postGitHubComment($review);

        } catch (Exception $e) {
            Log::error('Failed to process pull request review', [
                'pr_id' => $this->pullRequest->id,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts()
            ]);

            throw $e;
        }
    }

    public function failed(Exception $exception): void
    {
        Log::error('Pull request review permanently failed', [
            'pr_id' => $this->pullRequest->id,
            'error' => $exception->getMessage()
        ]);

        // Mark any pending review as failed
        $this->pullRequest->reviews()
            ->where('status', 'pending')
            ->update(['status' => 'failed']);
    }

    /**
     * Optional: Post review as GitHub comment
     */
    private function postGitHubComment($review): void
    {
        // Implement GitHub API call to post comment
        // This would require GitHub token and API integration
    }
}
