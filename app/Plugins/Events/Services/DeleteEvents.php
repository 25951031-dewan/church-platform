<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class DeleteEvents
{
    public function execute(array $eventIds): void
    {
        $events = Event::whereIn('id', $eventIds)->get();

        foreach ($events as $event) {
            $event->reactions()->delete();
            $event->comments()->delete();
            $event->rsvps()->delete();
            $event->delete();
        }
    }
}
