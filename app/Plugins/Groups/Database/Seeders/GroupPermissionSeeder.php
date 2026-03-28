<?php

namespace App\Plugins\Groups\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class GroupPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'groups' => [
                'groups.view' => 'View Groups',
                'groups.create' => 'Create Groups',
                'groups.update' => 'Edit Own Groups',
                'groups.update_any' => 'Edit Any Group',
                'groups.delete' => 'Delete Own Groups',
                'groups.delete_any' => 'Delete Any Group',
                'groups.join' => 'Join Groups',
                'groups.moderate_any' => 'Moderate Any Group',
                'groups.feature' => 'Feature Groups',
                'groups.manage_posts' => 'Manage Posts In Any Group',
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

        // Member-level: view, create, join
        $memberPerms = Permission::whereIn('name', [
            'groups.view', 'groups.create', 'groups.update', 'groups.delete', 'groups.join',
        ])->pluck('id');

        // Moderator-level: + moderate
        $moderatorPerms = Permission::whereIn('name', [
            'groups.view', 'groups.create', 'groups.update', 'groups.update_any',
            'groups.delete', 'groups.delete_any', 'groups.join',
            'groups.moderate_any', 'groups.manage_posts',
        ])->pluck('id');

        // Admin-level: all
        $allPerms = Permission::where('group', 'groups')->pluck('id');

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
