<?php

namespace App\Plugins\Library\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class LibraryPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'library' => [
                'library.view' => 'Browse Library',
                'library.read' => 'Read Books Online',
                'library.download' => 'Download Books',
                'library.create' => 'Add Books',
                'library.update' => 'Edit Books',
                'library.delete' => 'Delete Books',
                'library.manage_categories' => 'Manage Book Categories',
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

        $memberPerms = Permission::whereIn('name', [
            'library.view', 'library.read', 'library.download',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'library.view', 'library.read', 'library.download',
            'library.create', 'library.update',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'library')->pluck('id');

        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['moderator', 'ministry-leader'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($moderatorPerms);
        }

        foreach (['member'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
