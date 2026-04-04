<?php

namespace App\Listeners;

use App\Events\Prayer\PrayerRequestPrayed;
use Common\Notifications\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPrayerRequester implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PrayerRequestPrayed $event): void
    {
        $prayerRequest = $event->prayerRequest;
        $prayedBy = $event->prayedBy;
        
        // Don't notify if user prayed for their own request
        if ($prayerRequest->user_id === $prayedBy->id) {
            return;
        }

        // Don't notify anonymous prayer requests
        if ($prayerRequest->is_anonymous) {
            return;
        }

        \Log::info('Someone prayed for a prayer request', [
            'prayer_id' => $prayerRequest->id,
            'prayed_by' => $prayedBy->id,
            'requester_id' => $prayerRequest->user_id,
        ]);
    }
}
