<?php

namespace Common\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TwilioChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTwilio')) {
            return;
        }

        if (!$notifiable->phone_number) {
            return;
        }

        $message = $notification->toTwilio($notifiable);
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (!$sid || !$token) {
            return;
        }

        Http::withBasicAuth($sid, $token)->asForm()->post(
            "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
            [
                'From' => config('services.twilio.from'),
                'To' => $notifiable->phone_number,
                'Body' => is_array($message) ? ($message['body'] ?? '') : (string) $message,
            ]
        );
    }
}
