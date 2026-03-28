<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use App\Plugins\ChurchBuilder\Requests\ModifyChurchPage;
use App\Plugins\ChurchBuilder\Services\CrupdateChurchPage;
use App\Plugins\ChurchBuilder\Services\DeleteChurchPages;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchPageController extends Controller
{
    public function __construct(
        private CrupdateChurchPage $crupdate,
        private DeleteChurchPages $deleter,
    ) {}

    public function index(Church $church): JsonResponse
    {
        $pages = $church->publishedPages()
            ->select('id', 'church_id', 'title', 'slug', 'sort_order')
            ->get();

        return response()->json(['pages' => $pages]);
    }

    public function show(Church $church, ChurchPage $page): JsonResponse
    {
        Gate::authorize('view', $page);
        return response()->json(['page' => $page]);
    }

    public function store(ModifyChurchPage $request, Church $church): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $page = $this->crupdate->execute(array_merge($request->validated(), [
            'church_id' => $church->id,
            'created_by' => auth()->id(),
        ]));

        return response()->json(['page' => $page], 201);
    }

    public function update(ModifyChurchPage $request, Church $church, ChurchPage $page): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $page = $this->crupdate->execute($request->validated(), $page);

        return response()->json(['page' => $page]);
    }

    public function destroy(Church $church, ChurchPage $page): JsonResponse
    {
        Gate::authorize('managePages', $church);

        $this->deleter->execute([$page->id]);

        return response()->json(null, 204);
    }
}
