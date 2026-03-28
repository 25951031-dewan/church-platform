<?php

namespace App\Plugins\Groups\Services;

use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;

class CrupdateGroup
{
    public function execute(array $data, ?Group $group = null): Group
    {
        if ($group) {
            $group->update([
                'name' => $data['name'] ?? $group->name,
                'description' => $data['description'] ?? $group->description,
                'rules' => $data['rules'] ?? $group->rules,
                'type' => $data['type'] ?? $group->type,
                'cover_image' => $data['cover_image'] ?? $group->cover_image,
            ]);
        } else {
            $group = Group::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'rules' => $data['rules'] ?? null,
                'type' => $data['type'] ?? 'public',
                'cover_image' => $data['cover_image'] ?? null,
                'church_id' => $data['church_id'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            // Creator automatically becomes group admin
            GroupMember::create([
                'group_id' => $group->id,
                'user_id' => $data['created_by'],
                'role' => 'admin',
                'status' => 'approved',
                'joined_at' => now(),
            ]);

            $group->refreshMemberCount();
        }

        return $group;
    }
}
