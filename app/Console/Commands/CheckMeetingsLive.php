<?php

namespace App\Console\Commands;

use App\Plugins\LiveMeeting\Models\Meeting;
use Common\Notifications\Events\MeetingWentLive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckMeetingsLive extends Command
{
    protected $signature = 'meetings:check-live';
    protected $description = 'Dispatch live notifications for meetings that just started';

    public function handle(): int
    {
        $now = now();

        Meeting::query()
            ->active()
            ->whereBetween('starts_at', [$now->copy()->subMinutes(1), $now->copy()->addMinutes(1)])
            ->where('ends_at', '>=', $now)
            ->get()
            ->each(function (Meeting $meeting) {
                $cacheKey = "meeting-live-notified:{$meeting->id}:" . now()->toDateString();
                if (Cache::has($cacheKey)) {
                    return;
                }
                event(new MeetingWentLive($meeting));
                Cache::put($cacheKey, true, now()->endOfDay());
            });

        $this->info('Live meeting check completed.');
        return self::SUCCESS;
    }
}
