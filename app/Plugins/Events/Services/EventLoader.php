<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class EventLoader
{
    public function load(Event $event): Event
    {
        return $event->load([
            'creator:id,name,avatar',
        ])->loadCount(['rsvps', 'attendingRsvps', 'registrations', 'comments', 'reactions']);
    }

    public function loadForDetail(Event $event): array
    {
        $this->load($event);

        $data = $event->toArray();
        $data['rsvp_counts'] = $event->rsvpCounts();
        $data['reaction_counts'] = $event->reactionCounts();

        $userId = auth()->id();
        if ($userId) {
            $rsvp = $event->getUserRsvp($userId);
            $data['current_user_rsvp'] = $rsvp?->status;
            $data['current_user_reaction'] = $event->currentUserReaction()?->type;
        }

        return $data;
    }
}
