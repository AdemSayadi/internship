<?php
// App\Events\CodeSubmissionCreated.php

namespace App\Events;

use App\Models\CodeSubmission;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CodeSubmissionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $codeSubmission;
    public $user;

    public function __construct(CodeSubmission $codeSubmission, User $user)
    {
        $this->codeSubmission = $codeSubmission;
        $this->user = $user;
    }
}
