<?php

namespace App\Events;

use App\Models\Review;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReviewSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $review;
    public $user;

    public function __construct(Review $review, User $user)
    {
        $this->review = $review;
        $this->user = $user;
    }
}
