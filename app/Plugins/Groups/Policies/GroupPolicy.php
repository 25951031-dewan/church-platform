<?php

namespace App\Plugins\Groups\Policies;

use App\Models\User;
use App\Plugins\Groups\Models\Group;
use Common\Core\BasePolicy;

class GroupPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('groups.view');
    }

    public function view(User $user, Group $group): bool
    {
        // Public and church_only groups are visible to anyone authenticated
        if ($group->type !== 'private') {
            return true;
        }
        // Private groups: must be an approved member
        return $group->isApprovedMember($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('groups.create');
    }

    public function update(User $user, Group $group): bool
    {
        // Group admin can edit their own group
        if ($group->isGroupAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('groups.update_any');
    }

    public function delete(User $user, Group $group): bool
    {
        // Creator or group admin can delete
        if ($group->created_by === $user->id) {
            return $user->hasPermission('groups.delete');
        }
        return $user->hasPermission('groups.delete_any');
    }

    /**
     * Can this user manage members in this group?
     * (approve/reject requests, promote/demote, remove members)
     */
    public function manageMembers(User $user, Group $group): bool
    {
        if ($group->isGroupAdminOrModerator($user->id)) {
            return true;
        }
        return $user->hasPermission('groups.moderate_any');
    }

    public function join(User $user, Group $group): bool
    {
        if (!$user->hasPermission('groups.join')) {
            return false;
        }
        // Already a member? (controller should check, but policy backstop)
        return !$group->getMembership($user->id);
    }

    public function feature(User $user): bool
    {
        return $user->hasPermission('groups.feature');
    }
}
