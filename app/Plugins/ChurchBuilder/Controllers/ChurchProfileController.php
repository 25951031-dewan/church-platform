<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Services\ChurchLoader;
use App\Plugins\ChurchBuilder\Services\PaginateChurches;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchProfileController extends Controller
{
    public function __construct(
        private ChurchLoader $loader,
        private PaginateChurches $paginator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Church::class);
        $churches = $this->paginator->execute($request);
        return response()->json($churches);
    }

    public function show(Church $church): JsonResponse
    {
        Gate::authorize('view', $church);
        return response()->json(['church' => $this->loader->loadForDetail($church)]);
    }

    public function verify(Church $church): JsonResponse
    {
        Gate::authorize('verify', Church::class);

        $church->update([
            'is_verified' => !$church->is_verified,
            'verified_at' => $church->is_verified ? null : now(),
            'verified_by' => $church->is_verified ? null : auth()->id(),
        ]);

        return response()->json([
            'is_verified' => $church->is_verified,
            'verified_at' => $church->verified_at,
        ]);
    }

    public function feature(Church $church): JsonResponse
    {
        Gate::authorize('feature', Church::class);

        $church->update(['is_featured' => !$church->is_featured]);

        return response()->json([
            'is_featured' => $church->is_featured,
        ]);
    }
}
