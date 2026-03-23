<?php
namespace Plugins\Event\Services;

use Carbon\Carbon;
use Plugins\Event\Models\Event;
use RRule\RRule;

class RecurrenceExpander
{
    /**
     * Expand a recurring event's RRULE and return virtual occurrences within the date window.
     * Occurrences are NOT persisted — they are arrays with start_at/end_at overrides.
     *
     * @return array<array{start_at: Carbon, end_at: Carbon, parent: Event}>
     */
    public function expand(Event $parent, Carbon $from, Carbon $to): array
    {
        if (! $parent->is_recurring || ! $parent->recurrence_rule) {
            return [];
        }

        $rrule = new RRule($parent->recurrence_rule, $parent->start_at->toDateTimeString());
        $duration = $parent->start_at->diffInSeconds($parent->end_at);
        $occurrences = [];

        foreach ($rrule as $occurrence) {
            $start = Carbon::instance($occurrence);
            if ($start->gt($to)) break;
            if ($start->lt($from)) continue;

            $end = $start->copy()->addSeconds($duration);
            $occurrences[] = ['start_at' => $start, 'end_at' => $end, 'parent' => $parent];
        }

        return $occurrences;
    }

    /**
     * Materialise a virtual occurrence into a concrete events row.
     * Idempotent: returns existing row if already materialised.
     */
    public function materialise(Event $parent, Carbon $occurrenceDate): Event
    {
        $duration = $parent->start_at->diffInSeconds($parent->end_at);

        $existing = Event::where('recurrence_parent_id', $parent->id)
            ->whereDate('start_at', $occurrenceDate->toDateString())
            ->first();

        if ($existing) return $existing;

        $endAt = $occurrenceDate->copy()->addSeconds($duration);

        return Event::create(array_merge(
            $parent->only(['church_id', 'community_id', 'created_by', 'title', 'description', 'cover_image',
                'location', 'latitude', 'longitude', 'is_online', 'meeting_url', 'category', 'max_attendees', 'status']),
            [
                'start_at'             => $occurrenceDate,
                'end_at'               => $endAt,
                'is_recurring'         => false,
                'recurrence_parent_id' => $parent->id,
            ]
        ));
    }
}
