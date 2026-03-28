<?php

namespace Tests\Feature\Groups;

use App\Models\User;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Groups\Database\Seeders\GroupPermissionSeeder::class);
    }

    private function memberUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'member')->first());
        return $user;
    }

    private function adminUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'super-admin')->first());
        return $user;
    }

    public function test_member_can_list_groups(): void
    {
        $user = $this->memberUser();
        Group::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/groups')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_member_can_create_group(): void
    {
        $user = $this->memberUser();

        $response = $this->actingAs($user)->postJson('/api/v1/groups', [
            'name' => 'Youth Ministry',
            'description' => 'For young adults 18-30',
            'type' => 'public',
        ]);

        $response->assertCreated()
            ->assertJsonPath('group.name', 'Youth Ministry');

        // Creator should be auto-joined as admin
        $group = Group::where('name', 'Youth Ministry')->first();
        $this->assertNotNull($group);
        $membership = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($membership);
        $this->assertEquals('admin', $membership->role);
        $this->assertEquals('approved', $membership->status);
    }

    public function test_group_admin_can_update_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['created_by' => $user->id]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Updated Name',
        ])->assertOk()
            ->assertJsonPath('group.name', 'Updated Name');
    }

    public function test_non_admin_cannot_update_others_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/groups/{$group->id}", [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_admin_can_delete_group(): void
    {
        $admin = $this->adminUser();
        $group = Group::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/groups/{$group->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    public function test_admin_can_feature_group(): void
    {
        $admin = $this->adminUser();
        $group = Group::factory()->create(['is_featured' => false]);

        $this->actingAs($admin)->patchJson("/api/v1/groups/{$group->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }
}
