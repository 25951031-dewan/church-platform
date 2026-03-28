<?php

namespace Tests\Feature\Prayer;

use App\Models\User;
use App\Plugins\Prayer\Models\PrayerRequest;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Prayer\Database\Seeders\PrayerPermissionSeeder::class);
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

    public function test_member_can_view_public_prayer(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->create(['is_public' => true, 'status' => 'approved']);

        $this->actingAs($user)->getJson("/api/v1/prayer-requests/{$prayer->id}")
            ->assertOk();
    }

    public function test_member_cannot_view_private_prayer_of_another(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->create(['is_public' => false, 'status' => 'approved']);

        $this->actingAs($user)->getJson("/api/v1/prayer-requests/{$prayer->id}")
            ->assertForbidden();
    }

    public function test_member_can_view_own_private_prayer(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
            'status' => 'pending',
        ]);

        $this->actingAs($user)->getJson("/api/v1/prayer-requests/{$prayer->id}")
            ->assertOk();
    }

    public function test_member_cannot_moderate(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->pending()->create();

        $this->actingAs($user)->patchJson("/api/v1/prayer-requests/{$prayer->id}/moderate", [
            'status' => 'approved',
        ])->assertForbidden();
    }

    public function test_admin_can_flag_for_pastoral_care(): void
    {
        $admin = $this->adminUser();
        $prayer = PrayerRequest::factory()->create();

        $this->actingAs($admin)->patchJson("/api/v1/prayer-requests/{$prayer->id}/flag")
            ->assertOk()
            ->assertJsonPath('pastoral_flag', true);
    }
}
