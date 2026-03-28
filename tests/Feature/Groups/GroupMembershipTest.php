<?php

namespace Tests\Feature\Groups;

use App\Models\User;
use App\Plugins\Groups\Models\Group;
use App\Plugins\Groups\Models\GroupMember;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMembershipTest extends TestCase
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

    private function groupWithAdmin(): array
    {
        $admin = $this->memberUser();
        $group = Group::factory()->create(['created_by' => $admin->id]);
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $group->refreshMemberCount();
        return [$group, $admin];
    }

    public function test_user_can_join_public_group(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['type' => 'public']);

        $response = $this->actingAs($user)->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertCreated()
            ->assertJsonPath('status', 'approved');
    }

    public function test_joining_private_group_creates_pending_request(): void
    {
        $user = $this->memberUser();
        $group = Group::factory()->create(['type' => 'private']);

        $response = $this->actingAs($user)->postJson("/api/v1/groups/{$group->id}/join");

        $response->assertCreated()
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_group_admin_can_approve_pending_request(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $group->update(['type' => 'private']);

        $requester = $this->memberUser();
        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $requester->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/groups/{$group->id}/members/{$member->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'id' => $member->id,
            'status' => 'approved',
        ]);
    }

    public function test_group_admin_can_reject_pending_request(): void
    {
        [$group, $admin] = $this->groupWithAdmin();
        $group->update(['type' => 'private']);

        $requester = $this->memberUser();
        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $requester->id,
            'role' => 'member',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/groups/{$group->id}/members/{$member->id}/reject")
            ->assertNoContent();

        $this->assertDatabaseMissing('group_members', ['id' => $member->id]);
    }

    public function test_user_can_leave_group(): void
    {
        [$group, $admin] = $this->groupWithAdmin();

        $user = $this->memberUser();
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/groups/{$group->id}/leave")
            ->assertNoContent();

        $this->assertDatabaseMissing('group_members', [
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_sole_admin_cannot_leave(): void
    {
        [$group, $admin] = $this->groupWithAdmin();

        $this->actingAs($admin)->deleteJson("/api/v1/groups/{$group->id}/leave")
            ->assertStatus(422);
    }

    public function test_group_admin_can_change_member_role(): void
    {
        [$group, $admin] = $this->groupWithAdmin();

        $user = $this->memberUser();
        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/v1/groups/{$group->id}/members/{$member->id}/role", [
                'role' => 'moderator',
            ])
            ->assertOk();

        $this->assertDatabaseHas('group_members', [
            'id' => $member->id,
            'role' => 'moderator',
        ]);
    }

    public function test_group_admin_can_remove_member(): void
    {
        [$group, $admin] = $this->groupWithAdmin();

        $user = $this->memberUser();
        $member = GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/v1/groups/{$group->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('group_members', ['id' => $member->id]);
    }
}
