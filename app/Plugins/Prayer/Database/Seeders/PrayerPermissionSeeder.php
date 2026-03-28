<?php

namespace App\Plugins\Prayer\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class PrayerPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'prayer' => [
                'prayer.view' => 'View Prayer Requests',
                'prayer.create' => 'Submit Prayer Requests',
                'prayer.update' => 'Edit Own Prayers',
                'prayer.update_any' => 'Edit Any Prayer',
                'prayer.delete' => 'Delete Own Prayers',
                'prayer.delete_any' => 'Delete Any Prayer',
                'prayer.moderate' => 'Moderate Prayer Requests',
                'prayer.pastoral_flag' => 'Flag Prayers for Pastoral Care',
                'prayer.view_any' => 'View All Prayers (incl. private)',
                'prayer.view_anonymous' => 'View Anonymous Prayer Identity',
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
            'prayer.view', 'prayer.create', 'prayer.update', 'prayer.delete',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'prayer.view', 'prayer.create', 'prayer.update', 'prayer.update_any',
            'prayer.delete', 'prayer.delete_any', 'prayer.moderate', 'prayer.view_any',
        ])->pluck('id');

        $pastoralPerms = Permission::whereIn('name', [
            'prayer.view', 'prayer.create', 'prayer.update', 'prayer.update_any',
            'prayer.delete', 'prayer.delete_any', 'prayer.moderate',
            'prayer.pastoral_flag', 'prayer.view_any', 'prayer.view_anonymous',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'prayer')->pluck('id');

        foreach (['super-admin', 'platform-admin'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($pastoralPerms);
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
