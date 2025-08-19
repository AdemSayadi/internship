<?php
// App\Events\PullRequestCreated.php

namespace App\Events;

use App\Models\PullRequest;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PullRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pullRequest;
    public $user;

    public function __construct(PullRequest $pullRequest, User $user)
    {
        $this->pullRequest = $pullRequest;
        $this->user = $user;
    }
}
