<?php

namespace Tests\Feature\Events;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use App\Plugins\Events\Models\EventRsvp;
use Common\Auth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRsvpTest extends TestCase
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

    public function test_member_can_rsvp_attending(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'attending',
        ])->assertOk()
            ->assertJsonPath('rsvp.status', 'attending');
    }

    public function test_member_can_change_rsvp_status(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();
        EventRsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);

        $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", [
            'status' => 'interested',
        ])->assertOk()
            ->assertJsonPath('rsvp.status', 'interested');

        $this->assertDatabaseHas('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'interested',
        ]);
    }

    public function test_member_can_cancel_rsvp(): void
    {
        $user = $this->memberUser();
        $event = Event::factory()->create();
        EventRsvp::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'attending',
        ]);

        $this->actingAs($user)->deleteJson("/api/v1/events/{$event->id}/rsvp")
            ->assertOk();

        $this->assertDatabaseMissing('event_rsvps', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_rsvp_counts_returned(): void
    {
        $event = Event::factory()->create();
        $users = collect(range(1, 3))->map(fn () => $this->memberUser());

        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[0]->id, 'status' => 'attending']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[1]->id, 'status' => 'attending']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $users[2]->id, 'status' => 'interested']);

        $this->actingAs($users[0])->getJson("/api/v1/events/{$event->id}")
            ->assertOk()
            ->assertJsonPath('event.rsvp_counts.attending', 2)
            ->assertJsonPath('event.rsvp_counts.interested', 1);
    }

    public function test_attendees_list_ordered_by_status(): void
    {
        $event = Event::factory()->create();
        $user1 = $this->memberUser();
        $user2 = $this->memberUser();

        EventRsvp::create(['event_id' => $event->id, 'user_id' => $user1->id, 'status' => 'interested']);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $user2->id, 'status' => 'attending']);

        $response = $this->actingAs($user1)->getJson("/api/v1/events/{$event->id}/attendees")
            ->assertOk();

        $data = $response->json('data');
        $this->assertEquals('attending', $data[0]['status']);
        $this->assertEquals('interested', $data[1]['status']);
    }
}
