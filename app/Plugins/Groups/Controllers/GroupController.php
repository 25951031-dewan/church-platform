<?php

namespace App\Plugins\Groups\Controllers;

use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Requests\ModifyGroup;
use App\Plugins\Groups\Services\CrupdateGroup;
use App\Plugins\Groups\Services\DeleteGroups;
use App\Plugins\Groups\Services\GroupLoader;
use App\Plugins\Groups\Services\PaginateGroups;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class GroupController extends Controller
{
    public function __construct(
        private GroupLoader $loader,
        private CrupdateGroup $crupdate,
        private PaginateGroups $paginator,
        private DeleteGroups $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Group::class);
        $groups = $this->paginator->execute($request);
        return response()->json($groups);
    }

    public function show(Group $group): JsonResponse
    {
        Gate::authorize('view', $group);
        return response()->json(['group' => $this->loader->loadForDetail($group)]);
    }

    public function store(ModifyGroup $request): JsonResponse
    {
        Gate::authorize('create', Group::class);

        $group = $this->crupdate->execute([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'group' => $this->loader->loadForDetail($group),
        ], 201);
    }

    public function update(ModifyGroup $request, Group $group): JsonResponse
    {
        Gate::authorize('update', $group);

        $group = $this->crupdate->execute($request->validated(), $group);

        return response()->json([
            'group' => $this->loader->loadForDetail($group),
        ]);
    }

    public function destroy(Group $group): JsonResponse
    {
        Gate::authorize('delete', $group);

        $this->deleter->execute([$group->id]);

        return response()->noContent();
    }

    public function feature(Group $group): JsonResponse
    {
        Gate::authorize('feature', Group::class);

        $group->update(['is_featured' => !$group->is_featured]);

        return response()->json(['is_featured' => $group->is_featured]);
    }
}
