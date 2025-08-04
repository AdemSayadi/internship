<?php

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
                'message' => "A new review has been submitted for your code submission: {$codeSubmission->title}.",
                'read' => false,
            ]);

            Log::info('Notification created for review submitted', [
                'review_id' => $event->review->id,
                'user_id' => $codeSubmission->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for review submitted', [
                'review_id' => $event->review->id,
                'user_id' => $codeSubmission->user_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
