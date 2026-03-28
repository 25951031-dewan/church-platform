<?php

namespace App\Plugins\Prayer\Services;

use App\Plugins\Prayer\Models\PrayerRequest;

class DeletePrayerRequests
{
    public function execute(array $ids): void
    {
        $prayers = PrayerRequest::whereIn('id', $ids)->get();

        foreach ($prayers as $prayer) {
            $prayer->reactions()->delete();
            $prayer->updates()->delete();
            $prayer->delete();
        }
    }
}
