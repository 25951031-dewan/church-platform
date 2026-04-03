<?php

namespace App\Plugins\LiveMeeting\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class LiveMeetingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'live_meeting.view' => 'Browse and join meetings',
            'live_meeting.create' => 'Create meetings',
            'live_meeting.update' => 'Edit meetings',
            'live_meeting.delete' => 'Delete meetings',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'group' => 'live_meeting', 'type' => 'global']
            );
        }

        $memberPerms = Permission::whereIn('name', ['live_meeting.view'])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'live_meeting.view', 'live_meeting.create', 'live_meeting.update',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'live_meeting')->pluck('id');

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
