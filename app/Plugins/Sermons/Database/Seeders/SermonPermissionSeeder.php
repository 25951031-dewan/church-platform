<?php

namespace App\Plugins\Sermons\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class SermonPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'sermons' => [
                'sermons.view' => 'View Sermons',
                'sermons.create' => 'Upload Sermons',
                'sermons.update' => 'Edit Own Sermons',
                'sermons.update_any' => 'Edit Any Sermon',
                'sermons.delete' => 'Delete Own Sermons',
                'sermons.delete_any' => 'Delete Any Sermon',
                'sermons.manage_series' => 'Manage Sermon Series',
                'sermons.manage_speakers' => 'Manage Speakers',
                'sermons.feature' => 'Feature Sermons',
                'sermons.download' => 'Download Sermon Audio',
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
            'sermons.view', 'sermons.download',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'sermons.view', 'sermons.create', 'sermons.update', 'sermons.update_any',
            'sermons.delete', 'sermons.delete_any', 'sermons.download',
            'sermons.manage_series', 'sermons.manage_speakers',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'sermons')->pluck('id');

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
