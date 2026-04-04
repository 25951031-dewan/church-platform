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
            SettingsSeeder::class,
            \App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class,
            \App\Plugins\Groups\Database\Seeders\GroupPermissionSeeder::class,
            \App\Plugins\Events\Database\Seeders\EventPermissionSeeder::class,
            \App\Plugins\Sermons\Database\Seeders\SermonPermissionSeeder::class,
            \App\Plugins\Prayer\Database\Seeders\PrayerPermissionSeeder::class,
            \App\Plugins\Library\Database\Seeders\LibraryPermissionSeeder::class,
            \App\Plugins\Blog\Database\Seeders\BlogPermissionSeeder::class,
            \App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class,
            \App\Plugins\LiveMeeting\Database\Seeders\LiveMeetingPermissionSeeder::class,
            \Common\Chat\Database\Seeders\ChatPermissionSeeder::class,
        ]);

        // Optionally seed demo content (run with --class=DemoSeeder for just demo data)
    }
}
