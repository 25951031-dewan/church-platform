<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Event\Models\Event;

class AdminEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Event::with(['creator:id,name'])
                ->withCount('attendees')
                ->when($request->category, fn ($q) => $q->where('category', $request->category))
                ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
                ->latest('start_at')
                ->paginate(15)
        );
    }

    public function destroy(Event $event): JsonResponse
    {
        $event->delete();

        return response()->json(['message' => 'Event deleted']);
    }
}
