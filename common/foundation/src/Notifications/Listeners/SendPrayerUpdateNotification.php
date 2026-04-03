<?php

namespace Common\Notifications\Listeners;

use Common\Auth\Models\User;
use Common\Notifications\Events\PrayerUpdated;
use Common\Notifications\Notifications\PrayerUpdateNotification;
use Common\Notifications\Services\NotificationService;

class SendPrayerUpdateNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(PrayerUpdated $event): void
    {
        $request = $event->update->prayerRequest;
        if (!$request) {
            return;
        }

        $users = User::query()->where('id', $request->user_id)->get();
        $this->notifications->sendToMany($users, new PrayerUpdateNotification($event->update));
    }
}
