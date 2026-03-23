<?php
namespace Plugins\Event\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Plugins\Event\Models\Event;

class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Event $event) {}

    public function via(): array
    {
        return ['database'];
    }

    public function toDatabase(): array
    {
        return [
            'event_id'    => $this->event->id,
            'event_title' => $this->event->title,
            'start_at'    => $this->event->start_at->toIso8601String(),
            'message'     => "Reminder: \"{$this->event->title}\" starts in 24 hours.",
        ];
    }
}
