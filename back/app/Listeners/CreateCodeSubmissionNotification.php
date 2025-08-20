<?php
// App\Listeners\CreateCodeSubmissionNotification.php

namespace App\Listeners;

use App\Events\CodeSubmissionCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CreateCodeSubmissionNotification
{
    public function handle(CodeSubmissionCreated $event)
    {
        try {
            Notification::create([
                'user_id' => $event->codeSubmission->user_id,
                'review_id' => null, // Explicitly set to null
                'type' => Notification::TYPE_CODE_SUBMISSION_CREATED,
                'title' => 'Code Submission Created',
                'message' => "Your code submission '{$event->codeSubmission->title}' has been created and is pending review.",
                'data' => [
                    'code_submission_id' => $event->codeSubmission->id,
                    'title' => $event->codeSubmission->title,
                    'language' => $event->codeSubmission->language,
                ],
                'read' => false,
            ]);

            Log::info('Notification created for code submission', [
                'code_submission_id' => $event->codeSubmission->id,
                'user_id' => $event->codeSubmission->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for code submission', [
                'code_submission_id' => $event->codeSubmission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
