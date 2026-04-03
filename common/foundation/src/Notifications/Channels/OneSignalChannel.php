<?php

namespace Common\Notifications\Channels;

use Common\Notifications\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class OneSignalChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toOneSignal')) {
            return;
        }

        $payload = $notification->toOneSignal($notifiable);
        $playerIds = PushSubscription::where('user_id', $notifiable->id)->pluck('player_id')->all();

        if (empty($playerIds)) {
            return;
        }

        Http::withHeaders([
            'Authorization' => 'Basic ' . config('services.onesignal.rest_api_key'),
            'Content-Type' => 'application/json',
        ])->post('https://onesignal.com/api/v1/notifications', [
            'app_id' => config('services.onesignal.app_id'),
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $payload['title'] ?? config('app.name')],
            'contents' => ['en' => $payload['body'] ?? ''],
            'url' => $payload['url'] ?? null,
            'data' => $payload['data'] ?? [],
        ]);
    }
}
