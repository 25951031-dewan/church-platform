<?php

namespace App\Plugins\Events\Controllers;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Requests\ModifyEventRsvp;
use App\Plugins\Events\Services\EventRsvpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EventRsvpController extends Controller
{
    public function __construct(
        private EventRsvpService $rsvpService,
    ) {}

    public function rsvp(ModifyEventRsvp $request, Event $event): JsonResponse
    {
        Gate::authorize('rsvp', $event);

        $rsvp = $this->rsvpService->rsvp($event, auth()->id(), $request->input('status'));

        return response()->json([
            'rsvp' => $rsvp,
            'rsvp_counts' => $event->rsvpCounts(),
        ]);
    }

    public function cancel(Event $event): JsonResponse
    {
        $this->rsvpService->cancel($event, auth()->id());

        return response()->json([
            'rsvp_counts' => $event->rsvpCounts(),
        ]);
    }

    public function attendees(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);

        $attendees = $event->rsvps()
            ->with('user:id,name,avatar')
            ->orderByRaw("FIELD(status, 'attending', 'interested', 'not_going')")
            ->paginate(50);

        return response()->json($attendees);
    }
}
