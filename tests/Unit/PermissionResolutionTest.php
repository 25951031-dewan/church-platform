<?php

namespace Tests\Unit;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PermissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function createPermission(string $name, string $group = 'test'): Permission
    {
        return Permission::create([
            'name' => $name,
            'display_name' => ucwords(str_replace('.', ' ', $name)),
            'group' => $group,
        ]);
    }

    public function test_user_gets_permissions_from_role(): void
    {
        $perm = $this->createPermission('posts.create');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('posts.create'));
        $this->assertFalse($user->hasPermission('posts.delete'));
    }

    public function test_direct_grant_overrides_missing_role_permission(): void
    {
        $perm = $this->createPermission('posts.delete');
        $user = User::factory()->create();
        $user->directPermissions()->attach($perm, ['granted' => true]);
        $user->clearPermissionCache();

        $this->assertTrue($user->hasPermission('posts.delete'));
    }

    public function test_direct_deny_overrides_role_permission(): void
    {
        $perm = $this->createPermission('posts.create');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);
        $user->directPermissions()->attach($perm, ['granted' => false]);
        $user->clearPermissionCache();

        $this->assertFalse($user->hasPermission('posts.create'));
    }

    public function test_permissions_are_cached(): void
    {
        $perm = $this->createPermission('posts.view');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system']);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $this->assertTrue($user->hasPermission('posts.view'));

        $role->permissions()->detach($perm);

        // Should still return true (cached)
        $this->assertTrue($user->hasPermission('posts.view'));

        $user->clearPermissionCache();
        $this->assertFalse($user->hasPermission('posts.view'));
    }

    public function test_multiple_roles_merge_permissions(): void
    {
        $permA = $this->createPermission('posts.create');
        $permB = $this->createPermission('events.create');

        $roleA = Role::create(['name' => 'Writer', 'slug' => 'writer', 'type' => 'custom']);
        $roleA->permissions()->attach($permA);

        $roleB = Role::create(['name' => 'EventOrg', 'slug' => 'event-org', 'type' => 'custom']);
        $roleB->permissions()->attach($permB);

        $user = User::factory()->create();
        $user->roles()->attach([$roleA->id, $roleB->id]);

        $this->assertTrue($user->hasPermission('posts.create'));
        $this->assertTrue($user->hasPermission('events.create'));
    }

    public function test_role_level_returns_highest(): void
    {
        $roleA = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $roleB = Role::create(['name' => 'Moderator', 'slug' => 'moderator', 'type' => 'system', 'level' => 40]);

        $user = User::factory()->create();
        $user->roles()->attach([$roleA->id, $roleB->id]);

        $this->assertEquals(40, $user->getRoleLevel());
    }

    public function test_bootstrap_data_includes_permissions(): void
    {
        $perm = $this->createPermission('posts.view');
        $role = Role::create(['name' => 'Member', 'slug' => 'member', 'type' => 'system', 'level' => 20]);
        $role->permissions()->attach($perm);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        $data = $user->getBootstrapData();

        $this->assertArrayHasKey('permissions', $data);
        $this->assertTrue($data['permissions']['posts.view']);
        $this->assertEquals(20, $data['role_level']);
        $this->assertContains('member', $data['roles']);
    }
}
