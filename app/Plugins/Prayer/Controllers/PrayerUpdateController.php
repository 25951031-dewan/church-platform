<?php

namespace App\Plugins\Prayer\Controllers;

use App\Plugins\Prayer\Models\PrayerRequest;
use App\Plugins\Prayer\Models\PrayerUpdate;
use App\Plugins\Prayer\Requests\ModifyPrayerUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PrayerUpdateController extends Controller
{
    public function index(PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('view', $prayerRequest);

        $updates = $prayerRequest->updates()
            ->with('user:id,name,avatar')
            ->paginate(20);

        return response()->json($updates);
    }

    public function store(ModifyPrayerUpdate $request, PrayerRequest $prayerRequest): JsonResponse
    {
        // Only the prayer requester can add updates
        if ($prayerRequest->user_id !== auth()->id()) {
            Gate::authorize('moderate', PrayerRequest::class);
        }

        $update = PrayerUpdate::create([
            'prayer_request_id' => $prayerRequest->id,
            'user_id' => auth()->id(),
            'content' => $request->input('content'),
            'status_change' => $request->input('status_change', 'no_change'),
        ]);

        // If the update changes status, also update the prayer request
        $statusChange = $request->input('status_change', 'no_change');
        if ($statusChange === 'answered') {
            $prayerRequest->update(['status' => 'answered']);
        } elseif ($statusChange === 'still_praying') {
            $prayerRequest->update(['status' => 'praying']);
        }

        $update->load('user:id,name,avatar');

        return response()->json(['update' => $update], 201);
    }

    public function destroy(PrayerRequest $prayerRequest, PrayerUpdate $prayerUpdate): JsonResponse
    {
        // Only the update author or moderators can delete
        if ($prayerUpdate->user_id !== auth()->id()) {
            Gate::authorize('moderate', PrayerRequest::class);
        }

        $prayerUpdate->delete();

        return response()->noContent();
    }
}
