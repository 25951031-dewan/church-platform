<?php
use App\Models\User;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;

test('is_multi_day returns true when start and end are different dates', function () {
    $event = new Event([
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-02 17:00:00',
    ]);
    expect($event->is_multi_day)->toBeTrue();
});

test('is_multi_day returns false for same-day events', function () {
    $event = new Event([
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-01 17:00:00',
    ]);
    expect($event->is_multi_day)->toBeFalse();
});

test('authenticated user can create a platform-wide event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/events', [
        'title'    => 'Sunday Worship',
        'start_at' => '2026-06-01 09:00:00',
        'end_at'   => '2026-06-01 11:00:00',
        'category' => 'worship',
    ])->assertStatus(201)->assertJsonFragment(['title' => 'Sunday Worship']);
});

test('GET /events/{id} hides meeting_url for unauthenticated users', function () {
    $event = Event::factory()->create(['meeting_url' => 'https://zoom.us/j/abc', 'status' => 'published']);

    $this->getJson("/api/v1/events/{$event->id}")
        ->assertStatus(200)
        ->assertJsonPath('meeting_url', null);
});

test('GET /events/{id} shows meeting_url to going attendee', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['meeting_url' => 'https://zoom.us/j/abc', 'status' => 'published']);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);

    $this->actingAs($user)->getJson("/api/v1/events/{$event->id}")
        ->assertStatus(200)
        ->assertJsonPath('meeting_url', 'https://zoom.us/j/abc');
});

test('non-owner cannot update event', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $owner->id]);

    $this->actingAs($other)->patchJson("/api/v1/events/{$event->id}", ['title' => 'Changed'])
        ->assertStatus(403);
});

test('owner can update own event', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)->patchJson("/api/v1/events/{$event->id}", ['title' => 'Updated Title'])
        ->assertStatus(200)->assertJsonPath('title', 'Updated Title');
});

test('owner can delete own event', function () {
    $user  = User::factory()->create();
    $event = Event::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)->deleteJson("/api/v1/events/{$event->id}")
        ->assertStatus(200);
});

test('GET /events returns paginated list of published events', function () {
    Event::factory()->count(3)->create(['status' => 'published']);
    Event::factory()->create(['status' => 'draft']); // should not appear

    $this->getJson('/api/v1/events')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});
