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
            'churches.view'           => 'View Churches',
            'churches.create'         => 'Create Churches',
            'churches.update'         => 'Update Own Church',
            'churches.update_any'     => 'Update Any Church',
            'churches.delete'         => 'Delete Own Church',
            'churches.delete_any'     => 'Delete Any Church',
            'churches.verify'         => 'Verify Churches',
            'churches.feature'        => 'Feature Churches',
            'churches.manage_members' => 'Manage Church Members',
            'churches.manage_pages'   => 'Manage Church Pages',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'group' => 'churches', 'type' => 'global']
            );
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
            'super-admin'    => array_keys($permissions),
            'platform-admin' => array_keys($permissions),
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
