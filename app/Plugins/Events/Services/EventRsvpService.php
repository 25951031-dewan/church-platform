<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Models\EventRsvp;

class EventRsvpService
{
    public function rsvp(Event $event, int $userId, string $status): EventRsvp
    {
        return EventRsvp::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $userId],
            ['status' => $status]
        );
    }

    public function cancel(Event $event, int $userId): void
    {
        EventRsvp::where('event_id', $event->id)
            ->where('user_id', $userId)
            ->delete();
    }
}
