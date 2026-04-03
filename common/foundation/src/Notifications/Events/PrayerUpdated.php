<?php

namespace Common\Notifications\Events;

use App\Plugins\Prayer\Models\PrayerUpdate;

class PrayerUpdated
{
    public function __construct(public PrayerUpdate $update) {}
}
