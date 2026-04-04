<?php

namespace App\Plugins\Prayer\Services;

use App\Events\Prayer\PrayerRequestCreated;
use App\Plugins\Prayer\Models\PrayerRequest;

class CrupdatePrayerRequest
{
    public function execute(array $data, ?PrayerRequest $prayer = null): PrayerRequest
    {
        $fields = [
            'name', 'email', 'phone', 'subject', 'request', 'description',
            'status', 'is_public', 'is_anonymous', 'is_urgent', 'category',
        ];

        $isCreating = !$prayer;

        if ($prayer) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $prayer->update($updateData);
        } else {
            $createData = [
                'status' => $data['status'] ?? 'pending',
                'prayer_count' => 0,
            ];
            if (isset($data['user_id'])) {
                $createData['user_id'] = $data['user_id'];
            }
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $prayer = PrayerRequest::create($createData);
        }

        // Dispatch event for new prayer requests
        if ($isCreating) {
            event(new PrayerRequestCreated($prayer));
        }

        return $prayer;
    }
}
