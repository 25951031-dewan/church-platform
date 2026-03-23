<?php
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Plugins\Event\Jobs\SendEventRemindersJob;
use Plugins\Event\Models\Event;
use Plugins\Event\Models\EventAttendee;
use Plugins\Event\Notifications\EventReminderNotification;

test('reminder job sends to going attendees only', function () {
    Notification::fake();
    $event = Event::factory()->create([
        'start_at'         => now()->addHours(24),
        'end_at'           => now()->addHours(26),
        'status'           => 'published',
        'reminder_sent_at' => null,
    ]);
    $going = User::factory()->create();
    $maybe = User::factory()->create();
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $going->id, 'status' => 'going']);
    EventAttendee::create(['event_id' => $event->id, 'user_id' => $maybe->id, 'status' => 'maybe']);

    (new SendEventRemindersJob())->handle();

    Notification::assertSentTo($going, EventReminderNotification::class);
    Notification::assertNotSentTo($maybe, EventReminderNotification::class);
    expect($event->fresh()->reminder_sent_at)->not->toBeNull();
});

test('reminder job does not re-send', function () {
    Notification::fake();
    $event = Event::factory()->create([
        'start_at'         => now()->addHours(24),
        'status'           => 'published',
        'reminder_sent_at' => now(), // already sent
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});

test('reminder job does not fire for cancelled events', function () {
    Notification::fake();
    Event::factory()->create([
        'start_at'         => now()->addHours(24),
        'status'           => 'cancelled',
        'reminder_sent_at' => null,
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});

test('reminder job does not fire for past events', function () {
    Notification::fake();
    Event::factory()->create([
        'start_at'         => now()->subDay(),
        'status'           => 'published',
        'reminder_sent_at' => null,
    ]);
    (new SendEventRemindersJob())->handle();
    Notification::assertNothingSent();
});
