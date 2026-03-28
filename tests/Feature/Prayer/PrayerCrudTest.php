<?php

namespace Tests\Feature\Prayer;

use App\Models\User;
use App\Plugins\Prayer\Models\PrayerRequest;
use App\Plugins\Prayer\Models\PrayerUpdate;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerCrudTest extends TestCase
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

    public function test_member_can_list_prayer_requests(): void
    {
        $user = $this->memberUser();
        PrayerRequest::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/prayer-requests')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_member_can_submit_prayer(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/prayer-requests', [
            'name' => 'John Doe',
            'subject' => 'Healing prayer',
            'request' => 'Please pray for my recovery.',
            'is_public' => true,
            'category' => 'health',
        ])->assertCreated()
            ->assertJsonPath('prayer.subject', 'Healing prayer')
            ->assertJsonPath('prayer.category', 'health');
    }

    public function test_admin_can_update_any_prayer(): void
    {
        $admin = $this->adminUser();
        $prayer = PrayerRequest::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/prayer-requests/{$prayer->id}", [
            'subject' => 'Updated Subject',
        ])->assertOk()
            ->assertJsonPath('prayer.subject', 'Updated Subject');
    }

    public function test_admin_can_delete_prayer(): void
    {
        $admin = $this->adminUser();
        $prayer = PrayerRequest::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/prayer-requests/{$prayer->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('prayer_requests', ['id' => $prayer->id]);
    }

    public function test_admin_can_moderate_prayer_status(): void
    {
        $admin = $this->adminUser();
        $prayer = PrayerRequest::factory()->pending()->create();

        $this->actingAs($admin)->patchJson("/api/v1/prayer-requests/{$prayer->id}/moderate", [
            'status' => 'approved',
        ])->assertOk()
            ->assertJsonPath('prayer.status', 'approved');
    }

    public function test_requester_can_add_update(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson("/api/v1/prayer-requests/{$prayer->id}/updates", [
            'content' => 'God answered my prayer!',
            'status_change' => 'answered',
        ])->assertCreated()
            ->assertJsonPath('update.content', 'God answered my prayer!');

        // Prayer status should also be updated
        $this->assertEquals('answered', $prayer->fresh()->status);
    }

    public function test_delete_cascades_updates(): void
    {
        $admin = $this->adminUser();
        $prayer = PrayerRequest::factory()->create();
        PrayerUpdate::create([
            'prayer_request_id' => $prayer->id,
            'user_id' => $prayer->user_id,
            'content' => 'Test update',
        ]);

        $this->actingAs($admin)->deleteJson("/api/v1/prayer-requests/{$prayer->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('prayer_updates', ['prayer_request_id' => $prayer->id]);
    }
}
