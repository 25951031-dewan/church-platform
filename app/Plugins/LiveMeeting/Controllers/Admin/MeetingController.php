<?php

namespace App\Plugins\LiveMeeting\Controllers\Admin;

use App\Plugins\LiveMeeting\Models\Meeting;
use App\Plugins\LiveMeeting\Requests\ModifyMeeting;
use App\Plugins\LiveMeeting\Services\CrupdateMeeting;
use App\Plugins\LiveMeeting\Services\DeleteMeetings;
use App\Plugins\LiveMeeting\Services\PaginateMeetings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class MeetingController extends Controller
{
    public function __construct(
        private PaginateMeetings $paginator,
        private CrupdateMeeting $crupdater,
        private DeleteMeetings $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meeting::class);
        return response()->json(['pagination' => $this->paginator->execute($request->all())]);
    }

    public function show(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);
        return response()->json(['meeting' => $meeting->load(['host', 'event'])]);
    }

    public function store(ModifyMeeting $request): JsonResponse
    {
        Gate::authorize('create', Meeting::class);

        $meeting = $this->crupdater->execute(new Meeting(), [
            ...$request->validated(),
            'host_id' => $request->user()->id,
        ]);

        return response()->json(['meeting' => $meeting], 201);
    }

    public function update(ModifyMeeting $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('update', $meeting);
        return response()->json(['meeting' => $this->crupdater->execute($meeting, $request->validated())]);
    }

    public function destroy(Meeting $meeting): JsonResponse
    {
        Gate::authorize('delete', $meeting);
        $this->deleter->execute([$meeting->id]);
        return response()->noContent();
    }

    public function stats(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        $registrationCount = $meeting->registrations()->count();
        $attendanceCount = $meeting->registrations()->where('attended', true)->count();

        return response()->json([
            'stats' => [
                'registrations' => $registrationCount,
                'attended' => $attendanceCount,
                'attendance_rate' => $registrationCount > 0
                    ? round(($attendanceCount / $registrationCount) * 100, 2)
                    : 0,
            ],
        ]);
    }
}
