<?php
// Updated App\Listeners\CreateReviewCompletedNotification.php

namespace App\Listeners;

use App\Events\ReviewCompleted;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CreateReviewCompletedNotification
{
    public function handle(ReviewCompleted $event)
    {
        try {
            // Notify the user who owns the code submission
            $codeSubmission = $event->review->codeSubmission;
            Notification::create([
                'user_id' => $codeSubmission->user_id,
                'review_id' => $event->review->id,
                'type' => Notification::TYPE_REVIEW_COMPLETED,
                'title' => 'Review Completed',
                'message' => "The review for your code submission '{$codeSubmission->title}' has been completed with a score of {$event->review->overall_score}/10.",
                'data' => [
                    'review_id' => $event->review->id,
                    'code_submission_id' => $event->review->code_submission_id,
                    'title' => $codeSubmission->title,
                    'score' => $event->review->overall_score,
                ],
                'read' => false,
            ]);

            Log::info('Notification created for review completed', [
                'review_id' => $event->review->id,
                'user_id' => $codeSubmission->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for review completed', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
