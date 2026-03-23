<?php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;
use Plugins\Event\Policies\EventPolicy;

class EventController extends Controller
{
    /** GET /api/v1/events */
    public function index(Request $request): JsonResponse
    {
        $query = Event::published()->with(['creator:id,name,avatar'])->withCount('attendees');

        if ($request->church_id)    $query->where('church_id', $request->church_id);
        if ($request->community_id) $query->where('community_id', $request->community_id);
        if ($request->category)     $query->where('category', $request->category);
        if ($request->from)         $query->where('start_at', '>=', $request->from);
        if ($request->to)           $query->where('start_at', '<=', $request->to);

        match ($request->scope) {
            'upcoming' => $query->upcoming()->orderBy('start_at'),
            'past'     => $query->past()->orderByDesc('start_at'),
            default    => $query->orderBy('start_at'),
        };

        return response()->json($query->paginate(15));
    }

    /** GET /api/v1/events/{id} */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::with(['creator:id,name,avatar'])->findOrFail($id);
        $data  = $event->toArray();

        // Redact meeting_url unless authenticated and has a 'going' RSVP
        $user = $request->user();
        $hasGoingRsvp = $user && EventAttendee::where('event_id', $id)
            ->where('user_id', $user->id)->where('status', 'going')->exists();

        if (! $hasGoingRsvp) {
            $data['meeting_url'] = null;
        }

        return response()->json($data);
    }

    /** POST /api/v1/events */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'           => ['required', 'string', 'max:200'],
            'description'     => ['nullable', 'string'],
            'cover_image'     => ['nullable', 'url'],
            'start_at'        => ['required', 'date'],
            'end_at'          => ['required', 'date', 'after:start_at'],
            'location'        => ['nullable', 'string', 'max:300'],
            'latitude'        => ['nullable', 'numeric'],
            'longitude'       => ['nullable', 'numeric'],
            'is_online'       => ['boolean'],
            'meeting_url'     => ['nullable', 'url'],
            'is_recurring'    => ['boolean'],
            'recurrence_rule' => ['nullable', 'string'],
            'category'        => ['nullable', 'in:worship,youth,outreach,study,fellowship,other'],
            'max_attendees'   => ['nullable', 'integer', 'min:1'],
            'church_id'       => ['nullable', 'integer', 'exists:churches,id'],
            'community_id'    => ['nullable', 'integer', 'exists:communities,id'],
        ]);

        $policy = new EventPolicy();
        abort_unless(
            $policy->create($request->user(), $data['community_id'] ?? null, $data['church_id'] ?? null),
            403
        );

        $event = Event::create(array_merge($data, [
            'created_by' => $request->user()->id,
            'status'     => 'published',
        ]));

        return response()->json($event, 201);
    }

    /** PATCH /api/v1/events/{id} */
    public function update(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $policy = new EventPolicy();
        abort_unless($policy->update($request->user(), $event), 403);

        $data = $request->validate([
            'title'         => ['sometimes', 'string', 'max:200'],
            'description'   => ['nullable', 'string'],
            'start_at'      => ['sometimes', 'date'],
            'end_at'        => ['sometimes', 'date'],
            'location'      => ['nullable', 'string', 'max:300'],
            'is_online'     => ['sometimes', 'boolean'],
            'meeting_url'   => ['nullable', 'url'],
            'category'      => ['sometimes', 'in:worship,youth,outreach,study,fellowship,other'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
            'status'        => ['sometimes', 'in:published,draft,cancelled'],
        ]);

        $event->update($data);
        return response()->json($event);
    }

    /** DELETE /api/v1/events/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $policy = new EventPolicy();
        abort_unless($policy->delete($request->user(), $event), 403);

        $event->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    /** GET /api/v1/events/{id}/attendees */
    public function attendees(int $id): JsonResponse
    {
        $attendees = \Plugins\Event\Models\EventAttendee::with('user:id,name,avatar')
            ->where('event_id', $id)->where('status', 'going')->paginate(20);
        return response()->json($attendees);
    }
}
