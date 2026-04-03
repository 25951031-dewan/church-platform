<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EventController extends Controller
{
    /**
     * List all events with pagination.
     */
    public function index(): JsonResponse
    {
        $events = Event::orderBy('start_date', 'desc')->paginate(15);

        return response()->json($events);
    }

    /**
     * List upcoming events (today and future).
     */
    public function upcoming(): JsonResponse
    {
        $events = Event::where('start_date', '>=', now()->toDateString())
            ->orderBy('start_date', 'asc')
            ->paginate(15);

        return response()->json($events);
    }

    /**
     * Show a single event with its registrations count.
     */
    public function show(Event $event): JsonResponse
    {
        $event->loadCount('registrations');

        return response()->json([
            'event' => $event,
        ]);
    }

    /**
     * Store a new event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['required', 'string'],
            'start_date'           => ['required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time'           => ['nullable', 'string'],
            'end_time'             => ['nullable', 'string'],
            'location'             => ['nullable', 'string', 'max:255'],
            'image'                => ['nullable', 'string', 'max:255'],
            'max_attendees'        => ['nullable', 'integer', 'min:1'],
            'registration_required' => ['sometimes', 'boolean'],
            'is_active'            => ['sometimes', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['title']);

        // Ensure slug uniqueness
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Event::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully.',
            'event'   => $event,
        ], 201);
    }

    /**
     * Update an existing event.
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'title'                => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['sometimes', 'required', 'string'],
            'start_date'           => ['sometimes', 'required', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time'           => ['nullable', 'string'],
            'end_time'             => ['nullable', 'string'],
            'location'             => ['nullable', 'string', 'max:255'],
            'image'                => ['nullable', 'string', 'max:255'],
            'max_attendees'        => ['nullable', 'integer', 'min:1'],
            'registration_required' => ['sometimes', 'boolean'],
            'is_active'            => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);

            // Ensure slug uniqueness excluding current event
            $originalSlug = $validated['slug'];
            $counter = 1;
            while (Event::where('slug', $validated['slug'])->where('id', '!=', $event->id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully.',
            'event'   => $event->fresh(),
        ]);
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json([
            'message' => 'Event deleted successfully.',
        ]);
    }

    /**
     * Register a user/guest for an event.
     */
    public function register(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        // Check if max attendees limit is reached
        if ($event->max_attendees) {
            $currentCount = EventRegistration::where('event_id', $event->id)->count();
            if ($currentCount >= $event->max_attendees) {
                return response()->json([
                    'message' => 'This event has reached its maximum number of attendees.',
                ], 422);
            }
        }

        // Check for duplicate registration by email
        $existing = EventRegistration::where('event_id', $event->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'This email is already registered for this event.',
            ], 422);
        }

        $validated['event_id'] = $event->id;

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        $registration = EventRegistration::create($validated);

        return response()->json([
            'message'      => 'Successfully registered for the event.',
            'registration' => $registration,
        ], 201);
    }
}
