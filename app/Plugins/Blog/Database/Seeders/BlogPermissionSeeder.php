<?php

namespace App\Plugins\Blog\Database\Seeders;

use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Database\Seeder;

class BlogPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'blog.view' => 'Browse articles',
            'blog.create' => 'Create article drafts',
            'blog.update' => 'Edit articles',
            'blog.delete' => 'Delete articles',
            'blog.publish' => 'Publish or schedule articles',
            'blog.manage_categories' => 'Manage article categories',
            'blog.manage_tags' => 'Manage tags',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName, 'group' => 'blog', 'type' => 'global']
            );
        }

        $memberPerms = Permission::whereIn('name', ['blog.view'])->pluck('id');

        $moderatorPerms = Permission::whereIn('name', [
            'blog.view', 'blog.create', 'blog.update',
        ])->pluck('id');

        $allPerms = Permission::where('group', 'blog')->pluck('id');

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
