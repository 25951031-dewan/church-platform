<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Models\SermonSeries;
use App\Plugins\Sermons\Requests\ModifySermonSeries;
use App\Plugins\Sermons\Services\CrupdateSermonSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SermonSeriesController extends Controller
{
    public function __construct(
        private CrupdateSermonSeries $crupdate,
    ) {}

    public function index(): JsonResponse
    {
        $series = SermonSeries::withCount('sermons')
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($series);
    }

    public function show(SermonSeries $sermonSeries): JsonResponse
    {
        $sermonSeries->loadCount('sermons');

        return response()->json(['series' => $sermonSeries]);
    }

    public function store(ModifySermonSeries $request): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        $series = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['series' => $series], 201);
    }

    public function update(ModifySermonSeries $request, SermonSeries $sermonSeries): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        $series = $this->crupdate->execute($request->validated(), $sermonSeries);

        return response()->json(['series' => $series]);
    }

    public function destroy(SermonSeries $sermonSeries): JsonResponse
    {
        Gate::authorize('manageSeries', Sermon::class);

        $sermonSeries->sermons()->update(['series_id' => null]);
        $sermonSeries->delete();

        return response()->noContent();
    }
}
