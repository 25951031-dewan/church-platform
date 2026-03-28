<?php

namespace App\Plugins\Timeline\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class TimelinePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'posts' => [
                'posts.view' => 'View Posts',
                'posts.create' => 'Create Posts',
                'posts.update' => 'Edit Own Posts',
                'posts.update_any' => 'Edit Any Post',
                'posts.delete' => 'Delete Own Posts',
                'posts.delete_any' => 'Delete Any Post',
                'posts.pin' => 'Pin Posts',
                'posts.schedule' => 'Schedule Posts',
                'posts.moderate' => 'Moderate Posts',
                'posts.announce' => 'Create Announcements',
            ],
            'comments' => [
                'comments.create' => 'Post Comments',
                'comments.update' => 'Edit Own Comments',
                'comments.delete_any' => 'Delete Any Comment',
                'comments.moderate' => 'Moderate Comments',
            ],
            'reactions' => [
                'reactions.create' => 'React to Content',
            ],
        ];

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $name => $displayName) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    ['display_name' => $displayName, 'group' => $group, 'type' => 'global']
                );
            }
        }

        // Assign to roles
        $memberPerms = Permission::whereIn('name', [
            'posts.view', 'posts.create', 'posts.update', 'posts.delete',
            'comments.create', 'comments.update',
            'reactions.create',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'posts.view', 'posts.create', 'posts.update', 'posts.update_any',
            'posts.delete', 'posts.delete_any', 'posts.pin', 'posts.moderate',
            'comments.create', 'comments.update', 'comments.delete_any', 'comments.moderate',
            'reactions.create',
        ])->pluck('id');

        $allPerms = Permission::whereIn('group', ['posts', 'comments', 'reactions'])->pluck('id');

        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
        }

        foreach (['moderator'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
        }

        foreach (['ministry-leader', 'member'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
