<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\Speaker;
use App\Plugins\Sermons\Requests\ModifySpeaker;
use App\Plugins\Sermons\Services\CrupdateSpeaker;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SpeakerController extends Controller
{
    public function __construct(
        private CrupdateSpeaker $crupdate,
    ) {}

    public function index(): JsonResponse
    {
        $speakers = Speaker::withCount('sermons')
            ->orderBy('name')
            ->paginate(50);

        return response()->json($speakers);
    }

    public function show(Speaker $speaker): JsonResponse
    {
        $speaker->loadCount('sermons');

        return response()->json(['speaker' => $speaker]);
    }

    public function store(ModifySpeaker $request): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['speaker' => $speaker], 201);
    }

    public function update(ModifySpeaker $request, Speaker $speaker): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker = $this->crupdate->execute($request->validated(), $speaker);

        return response()->json(['speaker' => $speaker]);
    }

    public function destroy(Speaker $speaker): JsonResponse
    {
        Gate::authorize('manageSpeakers', Sermon::class);

        $speaker->sermons()->update(['speaker_id' => null]);
        $speaker->delete();

        return response()->noContent();
    }
}
