<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('verse:refresh')->dailyAt('00:01');
        $schedule->command('notifications:send-event-reminders 24h')->hourly();
        $schedule->command('notifications:send-event-reminders 1h')->everyFifteenMinutes();
        $schedule->command('meetings:check-live')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
