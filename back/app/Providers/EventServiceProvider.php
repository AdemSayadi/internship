<?php

namespace App\Providers;

use App\Events\ReviewSubmitted;
use App\Events\ReviewCompleted;
use App\Listeners\CreateReviewSubmittedNotification;
use App\Listeners\CreateReviewCompletedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        ReviewSubmitted::class => [
            CreateReviewSubmittedNotification::class,
        ],
        ReviewCompleted::class => [
            CreateReviewCompletedNotification::class,
        ],
    ];

    public function boot()
    {
        //
    }
}
