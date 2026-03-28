<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            \App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class,
        ]);
    }
}
