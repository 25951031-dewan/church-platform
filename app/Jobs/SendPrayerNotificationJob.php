<?php

namespace App\Jobs;

use App\Plugins\Prayer\Models\PrayerRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendPrayerNotificationJob extends BaseJob
{
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public PrayerRequest $prayerRequest
    ) {
        $this->churchId = $prayerRequest->church_id;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        Log::info("Sending prayer notification", [
            'prayer_id' => $this->prayerRequest->id,
            'church_id' => $this->churchId,
        ]);

        // Get church members who opted in for prayer notifications
        // This is a placeholder - implement actual notification logic
        
        // Example: Send to prayer team
        // $prayerTeam = $this->prayerRequest->church->prayerTeamMembers;
        // Notification::send($prayerTeam, new NewPrayerRequestNotification($this->prayerRequest));
    }

    public function tags(): array
    {
        return [
            'prayer',
            "prayer:{$this->prayerRequest->id}",
            ...parent::tags(),
        ];
    }
}
