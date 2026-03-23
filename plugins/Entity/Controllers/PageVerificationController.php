<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;

class PageVerificationController extends Controller
{
    /**
     * POST /api/v1/pages/{id}/verify
     * Request page verification. Page admin only.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $page = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($page->isAdmin($request->user()->id), 403);

        if ($page->is_verified) {
            return response()->json(['message' => 'Page is already verified.'], 422);
        }

        if ($page->verification_requested_at !== null) {
            return response()->json(['message' => 'Verification already requested.'], 422);
        }

        $page->update(['verification_requested_at' => now()]);

        return response()->json(['verification_requested' => true], 201);
    }
}
