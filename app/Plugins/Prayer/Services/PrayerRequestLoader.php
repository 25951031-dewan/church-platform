<?php

namespace App\Plugins\Prayer\Services;

use App\Plugins\Prayer\Models\PrayerRequest;

class PrayerRequestLoader
{
    public function load(PrayerRequest $prayer): PrayerRequest
    {
        return $prayer->load([
            'user:id,name,avatar',
            'updates.user:id,name,avatar',
        ])->loadCount(['reactions', 'updates']);
    }

    public function loadForDetail(PrayerRequest $prayer): array
    {
        $this->load($prayer);

        $data = $prayer->toArray();
        $data['prayer_count'] = $prayer->prayerCount();

        $userId = auth()->id();
        if ($userId) {
            $data['current_user_prayed'] = $prayer->userHasPrayed($userId);
        }

        // Hide identity for anonymous prayers (unless viewer is admin/pastor)
        $data = $this->sanitizeAnonymous($data);

        return $data;
    }

    public function sanitizeAnonymous(array $data): array
    {
        if (!($data['is_anonymous'] ?? false)) {
            return $data;
        }

        // Admins/pastors can see identity
        $user = auth()->user();
        if ($user && $user->hasPermission('prayer.view_anonymous')) {
            return $data;
        }

        $data['user'] = null;
        $data['user_id'] = null;
        $data['name'] = 'Anonymous';
        $data['email'] = null;
        $data['phone'] = null;

        return $data;
    }
}
