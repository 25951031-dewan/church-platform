<?php

namespace Tests\Feature\ChurchBuilder;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Models\ChurchMember;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChurchMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\ChurchBuilder\Database\Seeders\ChurchBuilderPermissionSeeder::class);
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

    public function test_user_can_join_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated()
            ->assertJsonPath('membership.role', 'member');

        $this->assertDatabaseHas('church_user', [
            'church_id' => $church->id,
            'user_id' => $user->id,
            'status' => 'approved',
        ]);
    }

    public function test_user_can_leave_church(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $user->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/churches/{$church->id}/leave")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_user', [
            'church_id' => $church->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_joining_is_idempotent(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated();
        $this->actingAs($user)->postJson("/api/v1/churches/{$church->id}/join")
            ->assertCreated();

        $this->assertEquals(1, ChurchMember::where([
            'church_id' => $church->id,
            'user_id' => $user->id,
        ])->count());
    }

    public function test_can_list_church_members(): void
    {
        $user = $this->memberUser();
        $church = Church::factory()->create(['status' => 'approved']);

        foreach (range(1, 3) as $_) {
            ChurchMember::create([
                'church_id' => $church->id,
                'user_id' => User::factory()->create()->id,
                'role' => 'member',
                'status' => 'approved',
                'joined_at' => now(),
            ]);
        }

        $this->actingAs($user)->getJson("/api/v1/churches/{$church->id}/members")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_remove_member(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $member = User::factory()->create();
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/v1/churches/{$church->id}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('church_user', [
            'church_id' => $church->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_admin_can_update_member_role(): void
    {
        $admin = $this->adminUser();
        $church = Church::factory()->create(['status' => 'approved', 'admin_user_id' => $admin->id]);
        $member = User::factory()->create();
        ChurchMember::create([
            'church_id' => $church->id,
            'user_id' => $member->id,
            'role' => 'member',
            'status' => 'approved',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)->patchJson("/api/v1/churches/{$church->id}/members/{$member->id}/role", [
            'role' => 'admin',
        ])->assertOk()
            ->assertJsonPath('member.role', 'admin');
    }
}
