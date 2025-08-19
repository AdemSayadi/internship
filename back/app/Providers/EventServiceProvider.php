<?php

namespace App\Providers;

use App\Events\CodeSubmissionCreated;
use App\Events\ReviewSubmitted;
use App\Events\ReviewCompleted;
use App\Events\PullRequestCreated;
use App\Events\PullRequestReviewCompleted;
use App\Listeners\CreateCodeSubmissionNotification;
use App\Listeners\CreateReviewSubmittedNotification;
use App\Listeners\CreateReviewCompletedNotification;
use App\Listeners\CreatePullRequestNotification;
use App\Listeners\CreatePullRequestReviewNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CodeSubmissionCreated::class => [
            CreateCodeSubmissionNotification::class,
        ],
        ReviewSubmitted::class => [
            CreateReviewSubmittedNotification::class,
        ],
        ReviewCompleted::class => [
            CreateReviewCompletedNotification::class,
        ],
        PullRequestCreated::class => [
            CreatePullRequestNotification::class,
        ],
        PullRequestReviewCompleted::class => [
            CreatePullRequestReviewNotification::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
