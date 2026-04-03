<?php

namespace Common\Notifications\Events;

use App\Plugins\Events\Models\Event;

class EventReminderDue
{
    public function __construct(public Event $event, public string $window) {}
}
