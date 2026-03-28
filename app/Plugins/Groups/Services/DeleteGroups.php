<?php

namespace App\Plugins\Groups\Services;

use App\Plugins\Groups\Models\Group;
use App\Plugins\Timeline\Services\DeletePosts;

class DeleteGroups
{
    public function __construct(private DeletePosts $deletePosts) {}

    public function execute(array $groupIds): void
    {
        $groups = Group::whereIn('id', $groupIds)->get();

        foreach ($groups as $group) {
            // Delete all group posts (cascades reactions/comments via DeletePosts)
            $postIds = $group->posts()->pluck('id')->toArray();
            if (!empty($postIds)) {
                $this->deletePosts->execute($postIds);
            }

            // Delete members
            $group->members()->delete();

            // Delete group
            $group->delete();
        }
    }
}
