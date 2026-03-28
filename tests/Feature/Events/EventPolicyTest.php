<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventPolicyTest extends TestCase
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

    public function test_member_can_view_event(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->getJson("/api/v1/events/{$event->id}")
            ->assertOk();
    }

    public function test_member_cannot_update_others_event(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_member_can_rsvp(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'attending',
        ])->assertOk();
    }

    public function test_super_admin_can_manage_any_event(): void
    {
        $admin = $this->adminUser();
        $event = Event::factory()->create();

        $this->actingAs($admin)->putJson("/api/v1/events/{$event->id}", [
            'title' => 'Admin Updated',
        ])->assertOk();

        $this->actingAs($admin)->deleteJson("/api/v1/events/{$event->id}")
            ->assertNoContent();
    }
}
