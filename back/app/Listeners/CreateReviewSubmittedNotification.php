<?php
// Updated App\Listeners\CreateReviewSubmittedNotification.php

namespace App\Listeners;

use App\Events\ReviewSubmitted;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CreateReviewSubmittedNotification
{
    public function handle(ReviewSubmitted $event)
    {
        try {
            // Notify the user who owns the code submission
            $codeSubmission = $event->review->codeSubmission;
            Notification::create([
                'user_id' => $codeSubmission->user_id,
                'review_id' => $event->review->id,
                'type' => Notification::TYPE_REVIEW_SUBMITTED, // Use constant instead of string
                'title' => 'Review Started',
                'message' => "A review has been started for your code submission: {$codeSubmission->title}.",
                'data' => [
                    'review_id' => $event->review->id,
                    'code_submission_id' => $event->review->code_submission_id,
                    'title' => $codeSubmission->title,
                ],
                'read' => false,
            ]);

            Log::info('Notification created for review submitted', [
                'review_id' => $event->review->id,
                'user_id' => $codeSubmission->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for review submitted', [
                'review_id' => $event->review->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
