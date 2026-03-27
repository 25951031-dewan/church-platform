<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * All platform permissions grouped by category.
     */
    public static array $allPermissions = [
        // Content
        'manage_posts',
        'manage_pages',
        'manage_sermons',
        'manage_books',
        'manage_bible_studies',
        'manage_testimonies',
        'manage_announcements',
        'manage_galleries',
        'manage_ministries',
        // Events
        'manage_events',
        'manage_event_registrations',
        // Prayer
        'manage_prayers',
        // Community
        'manage_community_posts',
        'manage_groups',
        'moderate_community',
        // Counseling
        'request_counseling',
        'view_counseling_assigned',
        'manage_counseling',
        // Churches (platform-level)
        'manage_churches',
        'approve_churches',
        'view_church_directory',
        // Users & Roles (platform-level)
        'manage_users',
        'manage_roles',
        'assign_roles',
        // Finance
        'manage_donations',
        // Settings
        'manage_settings',
        'manage_appearance',
        'manage_localizations',
        // Newsletter
        'manage_newsletter',
        'send_newsletter',
        // System
        'manage_menus',
        'manage_categories',
        'manage_contacts',
        'view_analytics',
    ];

    public function run(): void
    {
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super_admin',
                'description' => 'Full platform access. Can manage all churches, users, and settings.',
                'level'       => 100,
                'is_system'   => true,
                'permissions' => self::$allPermissions,
            ],
            [
                'name'        => 'Church Admin',
                'slug'        => 'church_admin',
                'description' => 'Full access within their church. Cannot manage other churches or platform users.',
                'level'       => 80,
                'is_system'   => true,
                'permissions' => array_values(array_diff(self::$allPermissions, [
                    'manage_churches',
                    'approve_churches',
                    'manage_users',
                    'manage_roles',
                ])),
            ],
            [
                'name'        => 'Counsellor',
                'slug'        => 'counsellor',
                'description' => 'Access to counseling module and assigned members. Can view prayer requests.',
                'level'       => 40,
                'is_system'   => true,
                'permissions' => [
                    'view_church_directory',
                    'request_counseling',
                    'view_counseling_assigned',
                    'manage_counseling',
                    'manage_prayers',
                ],
            ],
            [
                'name'        => 'Musician',
                'slug'        => 'musician',
                'description' => 'Manages sermons, events, and gallery content.',
                'level'       => 30,
                'is_system'   => true,
                'permissions' => [
                    'manage_sermons',
                    'manage_events',
                    'manage_galleries',
                    'view_church_directory',
                ],
            ],
            [
                'name'        => 'General User',
                'slug'        => 'general_user',
                'description' => 'Standard member. Can submit content and participate in community.',
                'level'       => 10,
                'is_system'   => true,
                'permissions' => [
                    'view_church_directory',
                    'request_counseling',
                    'manage_community_posts',
                ],
            ],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('Created/updated ' . count($roles) . ' system roles.');
    }
}
