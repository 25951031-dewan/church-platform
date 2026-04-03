<?php

namespace App\Console\Commands;

use App\Plugins\Events\Models\Event;
use Common\Notifications\Events\EventReminderDue;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature = 'notifications:send-event-reminders {window=24h}';
    protected $description = 'Dispatch event reminder notifications for upcoming events';

    public function handle(): int
    {
        $window = (string) $this->argument('window');
        $target = $window === '1h' ? now()->addHour() : now()->addDay();

        Event::query()
            ->where('is_active', true)
            ->whereBetween('start_date', [$target->copy()->subMinutes(5), $target->copy()->addMinutes(5)])
            ->get()
            ->each(fn(Event $event) => event(new EventReminderDue($event, $window)));

        $this->info("Event reminders dispatched for {$window} window.");
        return self::SUCCESS;
    }
}
