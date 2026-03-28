<?php

namespace App\Plugins\Groups\Services;

use App\Plugins\Groups\Models\Group;

class GroupLoader
{
    public function load(Group $group): Group
    {
        return $group->load([
            'creator:id,name,avatar',
        ])->loadCount(['approvedMembers', 'posts']);
    }

    public function loadForDetail(Group $group): array
    {
        $this->load($group);

        $data = $group->toArray();

        $userId = auth()->id();
        if ($userId) {
            $membership = $group->getMembership($userId);
            $data['current_user_membership'] = $membership ? [
                'role' => $membership->role,
                'status' => $membership->status,
            ] : null;
        }

        return $data;
    }
}
