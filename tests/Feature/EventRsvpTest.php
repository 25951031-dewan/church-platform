<?php
use App\Models\User;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

test('RSVP going increments going_count', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => null]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'going'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(1);
});

test('change from going to maybe decrements going_count and increments maybe_count atomically', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create();
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);
    $event->update(['going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'maybe'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
    expect($event->fresh()->maybe_count)->toBe(1);
});

test('RSVP going when event is full returns 422', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => 1, 'going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'going'])
        ->assertStatus(422)->assertJsonFragment(['message' => 'Event is full']);
});

test('RSVP maybe when event is full is allowed', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['max_attendees' => 1, 'going_count' => 1]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'maybe'])
        ->assertStatus(200);
});

test('not_going RSVP does not change any counter', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['going_count' => 0, 'maybe_count' => 0]);

    $this->actingAs($user)->postJson("/api/v1/events/{$event->id}/rsvp", ['status' => 'not_going'])
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
    expect($event->fresh()->maybe_count)->toBe(0);
});

test('remove RSVP decrements going_count', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['going_count' => 1]);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);

    $this->actingAs($user)->deleteJson("/api/v1/events/{$event->id}/rsvp")
        ->assertStatus(200);

    expect($event->fresh()->going_count)->toBe(0);
});
