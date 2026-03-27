<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Community\Models\Community;

class AdminCommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Community::when($request->search, fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%"))
                ->latest()
                ->paginate(15)
        );
    }

    public function destroy(Community $community): JsonResponse
    {
        $community->delete();

        return response()->json(['message' => 'Community deleted']);
    }
}
