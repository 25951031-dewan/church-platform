<?php

namespace Plugins\ChurchPage\Controllers;

use App\Models\Church;
use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChurchPageController extends Controller
{
    public function __construct(private readonly PlatformModeService $platform) {}

    /**
     * List churches (directory). Only available in multi-church mode.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Church::active()
            ->orderByDesc('is_featured')
            ->orderBy('name');

        $this->platform->scopeForMode($query);

        $churches = $query->paginate($request->integer('per_page', 20));

        return response()->json($churches);
    }

    /**
     * Show a single church page.
     */
    public function show(string $slug): JsonResponse
    {
        $church = Church::where('slug', $slug)->active()->firstOrFail();

        return response()->json($church);
    }

    /**
     * List members of a church.
     */
    public function members(Church $church, Request $request): JsonResponse
    {
        $members = $church->members()
            ->with('user:id,name,avatar')
            ->paginate($request->integer('per_page', 20));

        return response()->json($members);
    }
}
