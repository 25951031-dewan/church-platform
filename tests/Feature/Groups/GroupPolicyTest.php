<?php

namespace Tests\Feature\Groups;

use App\Models\User;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupPolicyTest extends TestCase
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

    public function test_anyone_can_view_public_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['type' => 'public']);

        $this->actingAs($user)->getJson("/api/v1/groups/{$group->id}")
            ->assertOk();
    }

    public function test_non_member_cannot_view_private_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['type' => 'private']);

        $this->actingAs($user)->getJson("/api/v1/groups/{$group->id}")
            ->assertForbidden();
    }

    public function test_member_can_view_private_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['type' => 'private']);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->getJson("/api/v1/groups/{$group->id}")
            ->assertOk();
    }

    public function test_non_admin_member_cannot_manage_members(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $target = $this->memberUser();
        $targetMember = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $target->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/groups/{$group->id}/members/{$targetMember->id}/approve")
            ->assertForbidden();
    }

    public function test_super_admin_bypasses_all_checks(): void
    {
        $admin = $this->adminUser();
        $group = Group::factory()->create(['type' => 'private']);

        // Super admin can view private group without being a member
        $this->actingAs($admin)->getJson("/api/v1/groups/{$group->id}")
            ->assertOk();

        // Super admin can delete any group
        $this->actingAs($admin)->deleteJson("/api/v1/groups/{$group->id}")
            ->assertNoContent();
    }
}
