<?php

namespace Database\Seeders;

use Common\Auth\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $groups = config('permissions');

        foreach ($groups as $group => $permissions) {
            foreach ($permissions as $name => $displayName) {
                Permission::firstOrCreate(
                    ['name' => $name],
                    [
                        'display_name' => $displayName,
                        'group' => $group,
                        'type' => 'global',
                    ]
                );
            }
        }
    }
}
