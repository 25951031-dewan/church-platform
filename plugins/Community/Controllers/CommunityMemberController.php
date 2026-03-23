<?php

namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

class CommunityMemberController extends Controller
{
    /** GET /communities/{id}/members — list all members (admin only) */
    public function index(Request $request, int $id): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $members = $community->communityMembers()
            ->with('user:id,name,avatar')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'moderator' THEN 1 ELSE 2 END")
            ->get();

        return response()->json($members);
    }

    /** POST /communities/{id}/members/{userId}/approve */
    public function approve(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $member = CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'pending')
            ->firstOrFail();

        $member->update(['status' => 'approved']);
        $community->increment('members_count');

        return response()->json(['message' => 'Approved.']);
    }

    /** DELETE /communities/{id}/members/{userId}/approve — reject */
    public function reject(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'pending')
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Rejected.']);
    }

    /** PATCH /communities/{id}/members/{userId} — update role */
    public function updateRole(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        $role = $request->validate(['role' => ['required', 'in:moderator,member']])['role'];

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'approved')
            ->firstOrFail()
            ->update(['role' => $role]);

        return response()->json(['message' => 'Role updated.']);
    }

    /** POST /communities/{id}/members/{userId}/ban */
    public function ban(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);
        abort_if($userId === $request->user()->id, 422, 'Cannot ban yourself.');

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->firstOrFail()
            ->update(['status' => 'banned']);

        return response()->json(['message' => 'Banned.']);
    }

    /** DELETE /communities/{id}/members/{userId}/ban — unban */
    public function unban(Request $request, int $id, int $userId): JsonResponse
    {
        $community = Community::findOrFail($id);
        abort_unless($community->isAdmin($request->user()->id), 403);

        CommunityMember::where(['community_id' => $id, 'user_id' => $userId])
            ->where('status', 'banned')
            ->firstOrFail()
            ->update(['status' => 'approved']);

        return response()->json(['message' => 'Unbanned.']);
    }
}
