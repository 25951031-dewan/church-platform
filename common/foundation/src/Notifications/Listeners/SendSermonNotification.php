<?php

namespace Common\Notifications\Listeners;

use Common\Auth\Models\User;
use Common\Notifications\Events\SermonPublished;
use Common\Notifications\Notifications\NewSermonNotification;
use Common\Notifications\Services\NotificationService;

class SendSermonNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(SermonPublished $event): void
    {
        $this->notifications->sendToMany(User::query()->get(), new NewSermonNotification($event->sermon));
    }
}
