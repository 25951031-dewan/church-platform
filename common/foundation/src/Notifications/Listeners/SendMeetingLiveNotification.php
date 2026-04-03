<?php

namespace Common\Notifications\Listeners;

use Common\Auth\Models\User;
use Common\Notifications\Events\MeetingWentLive;
use Common\Notifications\Notifications\MeetingLiveNotification;
use Common\Notifications\Services\NotificationService;

class SendMeetingLiveNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(MeetingWentLive $event): void
    {
        $meeting = $event->meeting;
        $recipientIds = $meeting->requires_registration
            ? $meeting->registrations()->pluck('user_id')->all()
            : User::query()->pluck('id')->all();

        $users = User::query()->whereIn('id', $recipientIds)->get();
        $this->notifications->sendToMany($users, new MeetingLiveNotification($meeting));
    }
}
