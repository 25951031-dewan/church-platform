<?php

namespace Common\Notifications\Listeners;

use Common\Auth\Models\User;
use Common\Notifications\Events\EventReminderDue;
use Common\Notifications\Notifications\EventReminderNotification;
use Common\Notifications\Services\NotificationService;

class SendEventReminderNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(EventReminderDue $event): void
    {
        $userIds = $event->event->rsvps()->where('status', 'attending')->pluck('user_id')->all();
        $users = User::query()->whereIn('id', $userIds)->get();
        $this->notifications->sendToMany($users, new EventReminderNotification($event->event, $event->window));
    }
}
