<?php
namespace Plugins\Event\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Plugins\Event\Models\Event;
use Plugins\Event\Notifications\EventReminderNotification;

class SendEventRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Event::published()
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_at', [now()->addHours(23), now()->addHours(25)])
            ->each(function (Event $event) {
                $event->attendees()
                    ->where('status', 'going')
                    ->with('user')
                    ->chunk(100, function ($attendees) use ($event) {
                        foreach ($attendees as $attendee) {
                            $attendee->user->notify(new EventReminderNotification($event));
                        }
                    });

                $event->update(['reminder_sent_at' => now()]);
            });
    }
}
