<?php
// App\Events\PullRequestReviewCompleted.php

namespace App\Events;

use App\Models\PullRequestReview;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PullRequestReviewCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pullRequestReview;
    public $user;

    public function __construct(PullRequestReview $pullRequestReview, User $user)
    {
        $this->pullRequestReview = $pullRequestReview;
        $this->user = $user;
    }
}
