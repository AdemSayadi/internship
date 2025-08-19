<?php
// App\Listeners\CreatePullRequestNotification.php

namespace App\Listeners;

use App\Events\PullRequestCreated;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class CreatePullRequestNotification
{
    public function handle(PullRequestCreated $event)
    {
        try {
            Notification::create([
                'user_id' => $event->pullRequest->user_id,
                'type' => Notification::TYPE_PULL_REQUEST_CREATED,
                'title' => 'Pull Request Created',
                'message' => "Your pull request '{$event->pullRequest->title}' has been created and is ready for review.",
                'data' => [
                    'pull_request_id' => $event->pullRequest->id,
                    'title' => $event->pullRequest->title,
                    'github_pr_number' => $event->pullRequest->github_pr_number,
                ],
                'read' => false,
            ]);

            Log::info('Notification created for pull request', [
                'pull_request_id' => $event->pullRequest->id,
                'user_id' => $event->pullRequest->user_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification for pull request', [
                'pull_request_id' => $event->pullRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
