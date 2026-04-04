<?php

namespace App\Events\Prayer;

use App\Events\BaseEvent;
use App\Plugins\Prayer\Models\PrayerRequest;
use App\Models\User;

class PrayerRequestPrayed extends BaseEvent
{
    /**
     * The prayer request instance.
     */
    public PrayerRequest $prayerRequest;

    /**
     * The user who prayed.
     */
    public User $prayedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(PrayerRequest $prayerRequest, User $prayedBy)
    {
        parent::__construct($prayedBy->id, $prayerRequest->church_id);
        $this->prayerRequest = $prayerRequest;
        $this->prayedBy = $prayedBy;
    }
}
