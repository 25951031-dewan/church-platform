<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class CrupdateMeeting
{
    public function execute(Meeting $meeting, array $data): Meeting
    {
        $attributes = [
            'title' => $data['title'] ?? $meeting->title,
            'description' => $data['description'] ?? $meeting->description,
            'meeting_url' => $data['meeting_url'] ?? $meeting->meeting_url,
            'platform' => $data['platform'] ?? $meeting->platform ?? 'other',
            'church_id' => $data['church_id'] ?? $meeting->church_id,
            'starts_at' => $data['starts_at'] ?? $meeting->starts_at,
            'ends_at' => $data['ends_at'] ?? $meeting->ends_at,
            'timezone' => $data['timezone'] ?? $meeting->timezone ?? 'UTC',
            'is_recurring' => $data['is_recurring'] ?? $meeting->is_recurring ?? false,
            'recurrence_rule' => $data['recurrence_rule'] ?? $meeting->recurrence_rule,
            'cover_image' => $data['cover_image'] ?? $meeting->cover_image,
            'is_active' => $data['is_active'] ?? $meeting->is_active ?? true,
        ];

        if (!$meeting->exists) {
            $attributes['host_id'] = $data['host_id'];
            $meeting = Meeting::create($attributes);
        } else {
            $meeting->update($attributes);
        }

        return $meeting->load(['host']);
    }
}
