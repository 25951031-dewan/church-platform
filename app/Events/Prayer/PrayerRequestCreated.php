<?php

namespace App\Events\Prayer;

use App\Events\BaseEvent;
use App\Plugins\Prayer\Models\PrayerRequest;

class PrayerRequestCreated extends BaseEvent
{
    /**
     * The prayer request instance.
     */
    public PrayerRequest $prayerRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(PrayerRequest $prayerRequest)
    {
        parent::__construct($prayerRequest->user_id, $prayerRequest->church_id);
        $this->prayerRequest = $prayerRequest;
    }
}
