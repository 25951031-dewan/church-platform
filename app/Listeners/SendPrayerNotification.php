<?php

namespace App\Listeners;

use App\Events\Prayer\PrayerRequestCreated;
use Common\Notifications\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPrayerNotification implements ShouldQueue
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
    public function handle(PrayerRequestCreated $event): void
    {
        $prayerRequest = $event->prayerRequest;
        
        // Skip if anonymous
        if ($prayerRequest->is_anonymous) {
            return;
        }

        // Notify church admins/pastors about new prayer request
        // This can be expanded based on notification preferences
        \Log::info('New prayer request created', [
            'prayer_id' => $prayerRequest->id,
            'user_id' => $event->userId,
            'church_id' => $event->churchId,
        ]);
    }
}
