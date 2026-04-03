<?php

namespace Common\Notifications\Services;

use Common\Auth\Models\User;
use Common\Notifications\Channels\OneSignalChannel;
use Common\Notifications\Channels\TwilioChannel;
use Common\Notifications\Models\NotificationLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Throwable;

class NotificationService
{
    public function sendToUser(User $user, Notification $notification): void
    {
        $channels = method_exists($notification, 'channelsFor')
            ? $notification->channelsFor($user)
            : $notification->via($user);

        $logs = collect($channels)->map(function (string $channel) use ($user) {
            $mappedChannel = match ($channel) {
                'mail' => 'email',
                OneSignalChannel::class => 'push',
                TwilioChannel::class => 'sms',
                default => $channel,
            };

            return NotificationLog::create([
                'notification_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'channel' => $mappedChannel,
                'status' => 'pending',
            ]);
        });

        try {
            $user->notify($notification);

            $dbNotificationId = $user->notifications()->latest('created_at')->value('id');

            $logs->each(function (NotificationLog $log) use ($dbNotificationId) {
                $log->update(['notification_id' => $dbNotificationId ?: $log->notification_id]);
                $log->markAsSent();
            });
        } catch (Throwable $e) {
            $logs->each(fn(NotificationLog $log) => $log->markAsFailed($e->getMessage()));
        }
    }

    public function sendToMany(iterable $users, Notification $notification): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->sendToUser($user, $notification);
            }
        }
    }
}
