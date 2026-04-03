<?php

namespace App\Providers;

use Common\Notifications\Events\EventReminderDue;
use Common\Notifications\Events\MeetingWentLive;
use Common\Notifications\Events\PrayerUpdated;
use Common\Notifications\Events\SermonPublished;
use Common\Notifications\Listeners\SendEventReminderNotification;
use Common\Notifications\Listeners\SendMeetingLiveNotification;
use Common\Notifications\Listeners\SendPrayerUpdateNotification;
use Common\Notifications\Listeners\SendSermonNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [SendEmailVerificationNotification::class],
        SermonPublished::class => [SendSermonNotification::class],
        PrayerUpdated::class => [SendPrayerUpdateNotification::class],
        EventReminderDue::class => [SendEventReminderNotification::class],
        MeetingWentLive::class => [SendMeetingLiveNotification::class],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
