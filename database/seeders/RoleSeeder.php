<?php

namespace Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::pluck('id', 'name');

        // Super Admin — ALL permissions
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'type' => 'system', 'level' => 100, 'is_default' => false]
        );
        $superAdmin->permissions()->sync($allPermissions->values());

        // Platform Admin — all except system-critical
        $platformAdmin = Role::firstOrCreate(
            ['slug' => 'platform-admin'],
            ['name' => 'Platform Admin', 'type' => 'system', 'level' => 80, 'is_default' => false]
        );
        $platformAdminPerms = $allPermissions->except([
            'settings.update', 'users.impersonate', 'roles.delete',
        ]);
        $platformAdmin->permissions()->sync($platformAdminPerms->values());

        // Church Admin
        $churchAdmin = Role::firstOrCreate(
            ['slug' => 'church-admin'],
            ['name' => 'Church Admin', 'type' => 'system', 'level' => 60, 'is_default' => false]
        );
        $churchAdminPerms = $allPermissions->only([
            'admin.access', 'admin.dashboard',
            'users.view', 'users.create', 'users.update',
            'roles.view', 'roles.assign',
            'settings.view',
            'files.upload', 'files.delete',
            'appearance.themes', 'appearance.menus',
            'localizations.view',
            'seo.manage', 'seo.sitemap',
        ]);
        $churchAdmin->permissions()->sync($churchAdminPerms->values());

        // Pastor / Elder
        $pastor = Role::firstOrCreate(
            ['slug' => 'pastor'],
            ['name' => 'Pastor / Elder', 'type' => 'system', 'level' => 50, 'is_default' => false]
        );
        $pastor->permissions()->sync($churchAdminPerms->values());

        // Moderator
        $moderator = Role::firstOrCreate(
            ['slug' => 'moderator'],
            ['name' => 'Moderator', 'type' => 'system', 'level' => 40, 'is_default' => false]
        );
        $moderatorPerms = $allPermissions->only([
            'admin.access', 'users.view', 'files.upload',
        ]);
        $moderator->permissions()->sync($moderatorPerms->values());

        // Ministry Leader
        $ministryLeader = Role::firstOrCreate(
            ['slug' => 'ministry-leader'],
            ['name' => 'Ministry Leader', 'type' => 'system', 'level' => 30, 'is_default' => false]
        );
        $ministryLeader->permissions()->sync($allPermissions->only([
            'files.upload',
        ])->values());

        // Member (default role)
        $member = Role::firstOrCreate(
            ['slug' => 'member'],
            ['name' => 'Member', 'type' => 'system', 'level' => 20, 'is_default' => true]
        );
        $member->permissions()->sync($allPermissions->only([
            'files.upload',
        ])->values());

        // Guest
        Role::firstOrCreate(
            ['slug' => 'guest'],
            ['name' => 'Guest', 'type' => 'system', 'level' => 10, 'is_default' => false]
        );
    }
}
