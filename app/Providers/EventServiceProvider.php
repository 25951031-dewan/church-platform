<?php

namespace App\Providers;

use App\Events\Chat\MessageSent;
use App\Events\Group\UserJoinedGroup;
use App\Events\Prayer\PrayerRequestCreated;
use App\Events\Prayer\PrayerRequestPrayed;
use App\Events\Sermon\SermonPublished as AppSermonPublished;
use App\Events\Timeline\PostCreated;
use App\Listeners\IndexForSearch;
use App\Listeners\NotifyGroupAdmins;
use App\Listeners\NotifyPostMentions;
use App\Listeners\NotifyPrayerRequester;
use App\Listeners\NotifySermonPublished;
use App\Listeners\SendPrayerNotification;
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
        // Laravel Auth Events
        Registered::class => [SendEmailVerificationNotification::class],
        
        // Common Foundation Events (existing)
        SermonPublished::class => [SendSermonNotification::class],
        PrayerUpdated::class => [SendPrayerUpdateNotification::class],
        EventReminderDue::class => [SendEventReminderNotification::class],
        MeetingWentLive::class => [SendMeetingLiveNotification::class],
        
        // App Domain Events
        PrayerRequestCreated::class => [
            SendPrayerNotification::class,
            IndexForSearch::class,
        ],
        PrayerRequestPrayed::class => [
            NotifyPrayerRequester::class,
        ],
        AppSermonPublished::class => [
            NotifySermonPublished::class,
            IndexForSearch::class,
        ],
        PostCreated::class => [
            NotifyPostMentions::class,
            IndexForSearch::class,
        ],
        UserJoinedGroup::class => [
            NotifyGroupAdmins::class,
        ],
        // MessageSent is handled via broadcasting, no listener needed
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
