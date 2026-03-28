<?php

namespace App\Plugins\ChurchBuilder\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class ChurchBuilderPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'churches.view',
            'churches.create',
            'churches.update',
            'churches.update_any',
            'churches.delete',
            'churches.delete_any',
            'churches.verify',
            'churches.feature',
            'churches.manage_members',
            'churches.manage_pages',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $rolePermissions = [
            'member' => [
                'churches.view',
                'churches.create',
            ],
            'church-admin' => [
                'churches.view',
                'churches.create',
                'churches.update',
                'churches.delete',
                'churches.manage_members',
                'churches.manage_pages',
            ],
            'moderator' => [
                'churches.view',
                'churches.create',
                'churches.update',
                'churches.update_any',
                'churches.delete',
                'churches.delete_any',
                'churches.manage_members',
                'churches.manage_pages',
            ],
            'super-admin' => $permissions,
            'platform-admin' => $permissions,
        ];

        foreach ($rolePermissions as $roleSlug => $permNames) {
            $role = Role::where('slug', $roleSlug)->first();
            if (!$role) {
                continue;
            }
            $ids = Permission::whereIn('name', $permNames)->pluck('id');
            $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}
