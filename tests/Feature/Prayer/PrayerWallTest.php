<?php

namespace Tests\Feature\Prayer;

use App\Models\User;
use App\Plugins\Prayer\Models\PrayerRequest;
use Common\Auth\Models\Role;
use Common\Reactions\Models\Reaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrayerWallTest extends TestCase
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

    public function test_wall_shows_only_public_approved_prayers(): void
    {
        $user = $this->memberUser();
        PrayerRequest::factory()->count(2)->create(['is_public' => true, 'status' => 'approved']);
        PrayerRequest::factory()->pending()->create(['is_public' => true]);
        PrayerRequest::factory()->create(['is_public' => false, 'status' => 'approved']);

        $this->actingAs($user)->getJson('/api/v1/prayer-requests?wall=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_wall_filters_by_category(): void
    {
        $user = $this->memberUser();
        PrayerRequest::factory()->withCategory('health')->create(['is_public' => true, 'status' => 'approved']);
        PrayerRequest::factory()->withCategory('family')->create(['is_public' => true, 'status' => 'approved']);

        $this->actingAs($user)->getJson('/api/v1/prayer-requests?wall=1&category=health')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_anonymous_prayer_hides_identity(): void
    {
        $user = $this->memberUser();
        PrayerRequest::factory()->anonymous()->create([
            'name' => 'Secret Person',
            'is_public' => true,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/prayer-requests?wall=1')
            ->assertOk();

        $data = $response->json('data');
        $this->assertEquals('Anonymous', $data[0]['name']);
        $this->assertNull($data[0]['user_id']);
    }

    public function test_i_prayed_reaction_works(): void
    {
        $user = $this->memberUser();
        $prayer = PrayerRequest::factory()->create(['is_public' => true, 'status' => 'approved']);

        // Toggle "I Prayed" via reaction system
        $this->actingAs($user)->postJson('/api/v1/reactions/toggle', [
            'reactable_id' => $prayer->id,
            'reactable_type' => 'prayer_request',
            'type' => 'pray',
        ])->assertCreated();

        $this->assertEquals(1, $prayer->prayerCount());
        $this->assertTrue($prayer->userHasPrayed($user->id));
    }

    public function test_pastoral_flag_filter(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'super-admin')->first());

        PrayerRequest::factory()->flagged()->create();
        PrayerRequest::factory()->create();

        $this->actingAs($admin)->getJson('/api/v1/prayer-requests?flagged=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
