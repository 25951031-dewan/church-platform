<?php

namespace Plugins\Post\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Plugins\Community\Models\CommunityMember;
use Plugins\Post\Models\Post;

class PostModerationController extends Controller
{
    /**
     * Toggle pin on a community post. Community admin only.
     * POST /api/v1/posts/{id}/pin
     */
    public function pin(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->whereNotNull('community_id')->findOrFail($id);

        abort_unless($this->isCommunityAdmin($request->user()->id, $post->community_id), 403);

        $post->update(['is_pinned' => ! $post->is_pinned]);

        return response()->json(['is_pinned' => $post->is_pinned]);
    }

    /**
     * Approve a community post. Community admin only.
     * POST /api/v1/posts/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $post = Post::whereNotNull('community_id')->findOrFail($id);

        abort_unless($this->isCommunityAdmin($request->user()->id, $post->community_id), 403);

        $post->update([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
        ]);

        return response()->json([
            'is_approved' => true,
            'approved_by' => $request->user()->id,
        ]);
    }

    private function isCommunityAdmin(int $userId, int $communityId): bool
    {
        return CommunityMember::where('community_id', $communityId)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->where('status', 'approved')
            ->exists();
    }
}
