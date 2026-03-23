<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;

class PageInsightsController extends Controller
{
    /**
     * GET /api/v1/pages/{id}/insights
     * Returns page analytics. Admin only.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $page = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($page->isAdmin($request->user()->id), 403);

        return response()->json([
            'members_count' => $page->approvedMembers()->count(),
            'sub_pages_count' => $page->subPages()->count(),
            'posts_count' => (int) $page->posts_count,
            'is_verified' => $page->is_verified,
            'verification_requested' => $page->verification_requested_at !== null,
            'created_at' => $page->created_at,
        ]);
    }
}
