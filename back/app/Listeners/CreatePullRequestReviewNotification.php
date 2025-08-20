<?php
// App\Listeners\CreatePullRequestReviewNotification.php

namespace App\Listeners;

use App\Events\PullRequestReviewCompleted;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CreatePullRequestReviewNotification
{
    public function handle(PullRequestReviewCompleted $event)
    {
        try {
            Notification::create([
                'user_id' => $event->pullRequestReview->pullRequest->user_id,
                'review_id' => null, // PR reviews don't have a direct review relationship
                'type' => Notification::TYPE_PR_REVIEW_COMPLETED,
                'title' => 'Pull Request Review Completed',
                'message' => "The review for your pull request '{$event->pullRequestReview->pullRequest->title}' has been completed.",
                'data' => [
                    'pull_request_review_id' => $event->pullRequestReview->id,
                    'pull_request_id' => $event->pullRequestReview->pull_request_id,
                    'score' => $event->pullRequestReview->score,
                    'review_type' => $event->pullRequestReview->review_type,
                    'title' => $event->pullRequestReview->pullRequest->title,
                ],
                'read' => false,
            ]);

            Log::info('Notification created for PR review completed', [
                'pr_review_id' => $event->pullRequestReview->id,
                'user_id' => $event->pullRequestReview->pullRequest->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for PR review', [
                'pr_review_id' => $event->pullRequestReview->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
