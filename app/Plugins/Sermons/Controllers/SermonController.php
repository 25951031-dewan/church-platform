<?php

namespace App\Plugins\Sermons\Controllers;

use App\Plugins\Sermons\Models\Sermon;
use App\Plugins\Sermons\Requests\ModifySermon;
use App\Plugins\Sermons\Services\CrupdateSermon;
use App\Plugins\Sermons\Services\DeleteSermons;
use App\Plugins\Sermons\Services\PaginateSermons;
use App\Plugins\Sermons\Services\SermonLoader;
use Common\Notifications\Events\SermonPublished;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SermonController extends Controller
{
    public function __construct(
        private SermonLoader $loader,
        private CrupdateSermon $crupdate,
        private PaginateSermons $paginator,
        private DeleteSermons $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Sermon::class);
        $sermons = $this->paginator->execute($request);
        return response()->json($sermons);
    }

    public function show(Sermon $sermon): JsonResponse
    {
        Gate::authorize('view', $sermon);
        $sermon->incrementView();
        return response()->json(['sermon' => $this->loader->loadForDetail($sermon)]);
    }

    public function store(ModifySermon $request): JsonResponse
    {
        Gate::authorize('create', Sermon::class);

        $sermon = $this->crupdate->execute([
            ...$request->validated(),
            'author_id' => $request->user()->id,
        ]);

        event(new SermonPublished($sermon));

        return response()->json([
            'sermon' => $this->loader->loadForDetail($sermon),
        ], 201);
    }

    public function update(ModifySermon $request, Sermon $sermon): JsonResponse
    {
        Gate::authorize('update', $sermon);

        $sermon = $this->crupdate->execute($request->validated(), $sermon);

        return response()->json([
            'sermon' => $this->loader->loadForDetail($sermon),
        ]);
    }

    public function destroy(Sermon $sermon): JsonResponse
    {
        Gate::authorize('delete', $sermon);

        $this->deleter->execute([$sermon->id]);

        return response()->noContent();
    }
}
