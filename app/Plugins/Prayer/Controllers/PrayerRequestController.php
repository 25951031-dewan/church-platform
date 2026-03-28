<?php

namespace App\Plugins\Prayer\Controllers;

use App\Plugins\Prayer\Models\PrayerRequest;
use App\Plugins\Prayer\Requests\ModifyPrayerRequest;
use App\Plugins\Prayer\Services\CrupdatePrayerRequest;
use App\Plugins\Prayer\Services\DeletePrayerRequests;
use App\Plugins\Prayer\Services\PaginatePrayerRequests;
use App\Plugins\Prayer\Services\PrayerRequestLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PrayerRequestController extends Controller
{
    public function __construct(
        private PrayerRequestLoader $loader,
        private CrupdatePrayerRequest $crupdate,
        private PaginatePrayerRequests $paginator,
        private DeletePrayerRequests $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PrayerRequest::class);
        $prayers = $this->paginator->execute($request);
        return response()->json($prayers);
    }

    public function show(PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('view', $prayerRequest);
        return response()->json(['prayer' => $this->loader->loadForDetail($prayerRequest)]);
    }

    public function store(ModifyPrayerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;

        $prayer = $this->crupdate->execute($data);

        return response()->json([
            'prayer' => $this->loader->loadForDetail($prayer),
        ], 201);
    }

    public function update(ModifyPrayerRequest $request, PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('update', $prayerRequest);

        $prayer = $this->crupdate->execute($request->validated(), $prayerRequest);

        return response()->json([
            'prayer' => $this->loader->loadForDetail($prayer),
        ]);
    }

    public function destroy(PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('delete', $prayerRequest);

        $this->deleter->execute([$prayerRequest->id]);

        return response()->noContent();
    }

    public function moderate(Request $request, PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('moderate', PrayerRequest::class);

        $validated = $request->validate([
            'status' => 'required|string|in:approved,praying,answered,pending',
        ]);

        $prayerRequest->update(['status' => $validated['status']]);

        return response()->json(['prayer' => $this->loader->loadForDetail($prayerRequest)]);
    }

    public function toggleFlag(PrayerRequest $prayerRequest): JsonResponse
    {
        Gate::authorize('flag', PrayerRequest::class);

        $prayerRequest->update([
            'pastoral_flag' => !$prayerRequest->pastoral_flag,
            'flagged_by' => $prayerRequest->pastoral_flag ? null : auth()->id(),
        ]);

        return response()->json([
            'pastoral_flag' => $prayerRequest->pastoral_flag,
            'flagged_by' => $prayerRequest->flagged_by,
        ]);
    }
}
