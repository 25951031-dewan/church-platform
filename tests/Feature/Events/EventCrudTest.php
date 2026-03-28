<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\App\Plugins\Timeline\Database\Seeders\TimelinePermissionSeeder::class);
        $this->seed(\App\Plugins\Events\Database\Seeders\EventPermissionSeeder::class);
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

    public function test_member_can_list_events(): void
    {
        $user = $this->memberUser();
        Event::factory()->count(3)->create();

        $this->actingAs($user)->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_event(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/api/v1/events', [
            'title' => 'Sunday Worship',
            'description' => 'Weekly worship service',
            'start_date' => now()->addWeek()->toDateTimeString(),
            'location' => 'Main Sanctuary',
            'meeting_url' => 'https://zoom.us/j/123456789',
        ])->assertCreated()
            ->assertJsonPath('event.title', 'Sunday Worship')
            ->assertJsonPath('event.meeting_url', 'https://zoom.us/j/123456789');
    }

    public function test_member_cannot_create_event(): void
    {
        $user = $this->memberUser();

        $this->actingAs($user)->postJson('/api/v1/events', [
            'title' => 'Test',
            'description' => 'Test',
            'start_date' => now()->addWeek()->toDateTimeString(),
        ])->assertForbidden();
    }

    public function test_admin_can_update_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Updated Title',
        ])->assertOk()
            ->assertJsonPath('event.title', 'Updated Title');
    }

    public function test_admin_can_delete_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/v1/events/{$event->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_admin_can_feature_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create(['is_featured' => false]);

        $this->actingAs($admin)->patchJson("/api/v1/events/{$event->id}/feature")
            ->assertOk()
            ->assertJsonPath('is_featured', true);
    }
}
