<?php

namespace Common\Notifications\Notifications;

use Common\Notifications\Channels\OneSignalChannel;
use Common\Notifications\Channels\TwilioChannel;
use Common\Notifications\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function type(): string;

    abstract public function title(): string;

    abstract public function body(): string;

    public function actionUrl(): ?string
    {
        return null;
    }

    public function channelsFor($notifiable): array
    {
        $preference = NotificationPreference::getForUser($notifiable->id, $this->type());
        $channels = [];

        if ($preference->in_app_enabled) {
            $channels[] = 'database';
        }
        if ($preference->email_enabled) {
            $channels[] = 'mail';
        }
        if ($preference->push_enabled) {
            $channels[] = OneSignalChannel::class;
        }
        if ($preference->sms_enabled) {
            $channels[] = TwilioChannel::class;
        }

        return $channels;
    }

    public function via($notifiable): array
    {
        return $this->channelsFor($notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->actionUrl(),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->line($this->body());

        if ($url = $this->actionUrl()) {
            $mail->action('View', url($url));
        }

        return $mail;
    }

    public function toOneSignal($notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'url' => $this->actionUrl() ? url($this->actionUrl()) : null,
            'data' => ['type' => $this->type()],
        ];
    }

    public function toTwilio($notifiable): array
    {
        return ['body' => $this->title() . ': ' . $this->body()];
    }
}
