<?php

namespace App\Plugins\Groups\Services;

use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;

class GroupMembershipService
{
    /**
     * Join a group. Returns the membership record.
     * Throws if already a member.
     */
    public function join(Group $group, int $userId): GroupMember
    {
        $existing = $group->getMembership($userId);
        if ($existing) {
            abort(409, 'Already a member or request pending.');
        }

        $status = match ($group->type) {
            'private' => 'pending',
            default => 'approved',
        };

        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'role' => 'member',
            'status' => $status,
            'joined_at' => $status === 'approved' ? now() : null,
        ]);

        if ($status === 'approved') {
            $group->refreshMemberCount();
        }

        return $member;
    }

    /**
     * Leave a group. Group admins cannot leave if they're the only admin.
     */
    public function leave(Group $group, int $userId): void
    {
        $member = $group->getMembership($userId);
        if (!$member) {
            abort(404, 'Not a member of this group.');
        }

        // Prevent last admin from leaving
        if ($member->isAdmin()) {
            $adminCount = $group->approvedMembers()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                abort(422, 'Cannot leave: you are the only admin. Transfer ownership first.');
            }
        }

        $member->delete();
        $group->refreshMemberCount();
    }

    /**
     * Approve a pending membership request (private groups).
     */
    public function approve(GroupMember $member): GroupMember
    {
        if ($member->status !== 'pending') {
            abort(422, 'Membership is not pending.');
        }

        $member->update([
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $member->group->refreshMemberCount();

        return $member;
    }

    /**
     * Reject (delete) a pending membership request.
     */
    public function reject(GroupMember $member): void
    {
        if ($member->status !== 'pending') {
            abort(422, 'Membership is not pending.');
        }

        $member->delete();
    }

    /**
     * Change a member's group role (admin/moderator/member).
     */
    public function changeRole(GroupMember $member, string $newRole): GroupMember
    {
        // Prevent demoting the last admin
        if ($member->isAdmin() && $newRole !== 'admin') {
            $adminCount = $member->group->approvedMembers()->where('role', 'admin')->count();
            if ($adminCount <= 1) {
                abort(422, 'Cannot demote: this is the only group admin.');
            }
        }

        $member->update(['role' => $newRole]);

        return $member;
    }

    /**
     * Remove a member from the group.
     */
    public function remove(Group $group, GroupMember $member): void
    {
        // Cannot remove yourself — use leave instead
        if ($member->user_id === auth()->id()) {
            abort(422, 'Use leave instead of remove for yourself.');
        }

        // Cannot remove another admin
        if ($member->isAdmin()) {
            abort(422, 'Cannot remove a group admin.');
        }

        $member->delete();
        $group->refreshMemberCount();
    }
}
