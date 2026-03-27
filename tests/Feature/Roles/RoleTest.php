<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Common\Auth\Models\Permission;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_super_admin_can_list_roles(): void
    {
        $user = $this->actingAsSuperAdmin();

        $this->actingAs($user)->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonStructure(['roles' => [['id', 'name', 'slug', 'level', 'permissions']]]);
    }

    public function test_super_admin_can_create_role(): void
    {
        $user = $this->actingAsSuperAdmin();
        $permIds = Permission::whereIn('name', ['files.upload'])->pluck('id')->toArray();

        $this->actingAs($user)->postJson('/api/v1/roles', [
            'name' => 'Worship Leader',
            'description' => 'Manages worship content',
            'level' => 35,
            'permissions' => $permIds,
        ])
            ->assertStatus(201)
            ->assertJsonPath('role.slug', 'worship-leader');
    }

    public function test_cannot_delete_system_roles(): void
    {
        $user = $this->actingAsSuperAdmin();
        $memberRole = Role::where('slug', 'member')->first();

        $this->actingAs($user)->deleteJson("/api/v1/roles/{$memberRole->id}")
            ->assertStatus(403);
    }

    public function test_member_cannot_access_roles(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());

        $this->actingAs($user)->getJson('/api/v1/roles')
            ->assertStatus(403);
    }
}
