<?php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

class EventRsvpController extends Controller
{
    /** POST /api/v1/events/{id}/rsvp */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:going,maybe,not_going'],
        ]);

        $result = DB::transaction(function () use ($id, $data, $request) {
            $event     = Event::lockForUpdate()->findOrFail($id);
            $newStatus = $data['status'];

            // Capacity check for 'going' only
            if ($newStatus === 'going' && $event->max_attendees !== null) {
                if ($event->going_count >= $event->max_attendees) {
                    abort(422, 'Event is full');
                }
            }

            $existing  = EventAttendee::where('event_id', $id)
                ->where('user_id', $request->user()->id)->first();
            $oldStatus = $existing?->status;

            // Decrement old counter
            if ($oldStatus === 'going')     $event->decrement('going_count');
            elseif ($oldStatus === 'maybe') $event->decrement('maybe_count');

            // Upsert RSVP row
            EventAttendee::updateOrCreate(
                ['event_id' => $id, 'user_id' => $request->user()->id],
                ['status'   => $newStatus]
            );

            // Increment new counter (not_going has no counter column)
            if ($newStatus === 'going')     $event->increment('going_count');
            elseif ($newStatus === 'maybe') $event->increment('maybe_count');

            return $event->fresh(['attendees']);
        });

        return response()->json(['status' => $data['status'], 'event' => $result]);
    }

    /** DELETE /api/v1/events/{id}/rsvp */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $attendee = EventAttendee::where('event_id', $id)
            ->where('user_id', $request->user()->id)->firstOrFail();

        DB::transaction(function () use ($id, $attendee) {
            $event = Event::lockForUpdate()->findOrFail($id);
            $attendee->delete();
            if ($attendee->status === 'going')     $event->decrement('going_count');
            elseif ($attendee->status === 'maybe') $event->decrement('maybe_count');
        });

        return response()->json(['message' => 'RSVP removed.']);
    }
}
