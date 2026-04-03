<?php

namespace Common\Chat\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class ChatPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'chat.send' => 'Send Messages',
            'chat.create_group' => 'Create Group Chats',
            'chat.attach_files' => 'Attach Files in Chat',
            'chat.moderate' => 'Moderate Chat',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'group' => 'chat', 'type' => 'global']
            );
        }

        $memberPerms = Permission::whereIn('name', ['chat.send'])->pluck('id');
        $allPerms = Permission::where('group', 'chat')->pluck('id');

        foreach (['super-admin', 'platform-admin', 'church-admin', 'pastor'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($allPerms);
        }

        foreach (['moderator', 'ministry-leader', 'member'] as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) $role->permissions()->syncWithoutDetaching($memberPerms);
        }
    }
}
