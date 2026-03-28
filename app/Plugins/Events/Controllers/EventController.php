<?php

namespace App\Plugins\Events\Controllers;

use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Requests\ModifyEvent;
use App\Plugins\Events\Services\CrupdateEvent;
use App\Plugins\Events\Services\DeleteEvents;
use App\Plugins\Events\Services\EventLoader;
use App\Plugins\Events\Services\PaginateEvents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    public function __construct(
        private EventLoader $loader,
        private CrupdateEvent $crupdate,
        private PaginateEvents $paginator,
        private DeleteEvents $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);
        $events = $this->paginator->execute($request);
        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        Gate::authorize('view', $event);
        return response()->json(['event' => $this->loader->loadForDetail($event)]);
    }

    public function store(ModifyEvent $request): JsonResponse
    {
        Gate::authorize('create', Event::class);

        $event = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'event' => $this->loader->loadForDetail($event),
        ], 201);
    }

    public function update(ModifyEvent $request, Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $event = $this->crupdate->execute($request->validated(), $event);

        return response()->json([
            'event' => $this->loader->loadForDetail($event),
        ]);
    }

    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $this->deleter->execute([$event->id]);

        return response()->noContent();
    }

    public function feature(Event $event): JsonResponse
    {
        Gate::authorize('feature', Event::class);

        $event->update(['is_featured' => !$event->is_featured]);

        return response()->json(['is_featured' => $event->is_featured]);
    }
}
