<?php

namespace App\Plugins\Events\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class EventPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'events' => [
                'events.view' => 'View Events',
                'events.create' => 'Create Events',
                'events.update' => 'Edit Own Events',
                'events.update_any' => 'Edit Any Event',
                'events.delete' => 'Delete Own Events',
                'events.delete_any' => 'Delete Any Event',
                'events.rsvp' => 'RSVP to Events',
                'events.manage_rsvp' => 'View/Export RSVP Lists',
                'events.feature' => 'Feature Events',
                'events.manage_registrations' => 'Manage Event Registrations',
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
            'events.view', 'events.rsvp',
        ])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'events.view', 'events.create', 'events.update', 'events.update_any',
            'events.delete', 'events.delete_any', 'events.rsvp',
            'events.manage_rsvp', 'events.manage_registrations',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'events')->pluck('id');

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
