<?php

namespace App\Plugins\Groups\Controllers;

use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;
use App\Plugins\Groups\Requests\ModifyGroupMember;
use App\Plugins\Groups\Services\GroupMembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class GroupMemberController extends Controller
{
    public function __construct(
        private GroupMembershipService $membership,
    ) {}

    /**
     * List group members (approved by default, ?status=pending for requests).
     */
    public function index(Request $request, Group $group): JsonResponse
    {
        Gate::authorize('view', $group);

        $query = $group->members()
            ->with('user:id,name,avatar')
            ->where('status', $request->input('status', 'approved'))
            ->orderByRaw("FIELD(role, 'admin', 'moderator', 'member')");

        $members = $query->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json($members);
    }

    /**
     * Join a group.
     */
    public function join(Group $group): JsonResponse
    {
        Gate::authorize('join', $group);

        $member = $this->membership->join($group, auth()->id());

        return response()->json([
            'member' => $member->load('user:id,name,avatar'),
            'status' => $member->status,
        ], 201);
    }

    /**
     * Leave a group.
     */
    public function leave(Group $group): JsonResponse
    {
        $this->membership->leave($group, auth()->id());

        return response()->noContent();
    }

    /**
     * Approve a pending membership request.
     */
    public function approve(Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('manageMembers', $group);

        $member = $this->membership->approve($member);

        return response()->json(['member' => $member->load('user:id,name,avatar')]);
    }

    /**
     * Reject a pending membership request.
     */
    public function reject(Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('manageMembers', $group);

        $this->membership->reject($member);

        return response()->noContent();
    }

    /**
     * Change a member's role (promote/demote).
     */
    public function changeRole(ModifyGroupMember $request, Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('manageMembers', $group);

        $member = $this->membership->changeRole($member, $request->input('role'));

        return response()->json(['member' => $member->load('user:id,name,avatar')]);
    }

    /**
     * Remove a member from the group.
     */
    public function remove(Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('manageMembers', $group);

        $this->membership->remove($group, $member);

        return response()->noContent();
    }
}
