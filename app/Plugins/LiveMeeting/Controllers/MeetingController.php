<?php

namespace App\Plugins\LiveMeeting\Controllers;

use App\Plugins\LiveMeeting\Models\Meeting;
use App\Plugins\LiveMeeting\Requests\ModifyMeeting;
use App\Plugins\LiveMeeting\Services\CrupdateMeeting;
use App\Plugins\LiveMeeting\Services\DeleteMeetings;
use App\Plugins\LiveMeeting\Services\MeetingLoader;
use App\Plugins\LiveMeeting\Services\PaginateMeetings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MeetingController
{
    public function __construct(
        private MeetingLoader $loader,
        private PaginateMeetings $paginator,
        private CrupdateMeeting $crupdater,
        private DeleteMeetings $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meeting::class);

        $results = $this->paginator->execute($request->all());

        return response()->json(['pagination' => $results]);
    }

    public function live(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Meeting::class);

        $meetings = Meeting::query()
            ->with(['host'])
            ->active()
            ->live()
            ->orderBy('starts_at')
            ->get();

        return response()->json(['meetings' => $meetings]);
    }

    public function show(Meeting $meeting): JsonResponse
    {
        Gate::authorize('view', $meeting);

        return response()->json($this->loader->loadForDetail($meeting));
    }

    public function store(ModifyMeeting $request): JsonResponse
    {
        Gate::authorize('create', Meeting::class);

        $data = $request->validated();
        $data['host_id'] = $request->user()->id;

        $meeting = $this->crupdater->execute(new Meeting(), $data);

        return response()->json(['meeting' => $meeting], 201);
    }

    public function update(ModifyMeeting $request, Meeting $meeting): JsonResponse
    {
        Gate::authorize('update', $meeting);

        $meeting = $this->crupdater->execute($meeting, $request->validated());

        return response()->json(['meeting' => $meeting]);
    }

    public function destroy(Meeting $meeting): JsonResponse
    {
        Gate::authorize('delete', $meeting);

        $this->deleter->execute([$meeting->id]);

        return response()->json(null, 204);
    }
}
