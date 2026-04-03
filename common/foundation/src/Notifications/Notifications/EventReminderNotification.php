<?php

namespace Common\Notifications\Notifications;

use App\Plugins\Events\Models\Event;

class EventReminderNotification extends BaseNotification
{
    public function __construct(private Event $event, private string $window = '24h') {}

    public function type(): string { return 'event'; }
    public function title(): string { return "Event reminder ({$this->window})"; }
    public function body(): string { return $this->event->title . ' starts at ' . $this->event->start_date?->toDateTimeString(); }
    public function actionUrl(): ?string { return "/events/{$this->event->id}"; }
}
