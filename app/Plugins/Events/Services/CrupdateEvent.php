<?php

namespace App\Plugins\Events\Services;

use App\Plugins\Events\Models\Event;

class CrupdateEvent
{
    public function execute(array $data, ?Event $event = null): Event
    {
        $fields = [
            'title', 'description', 'content', 'image', 'location', 'location_url',
            'start_date', 'end_date', 'is_recurring', 'recurrence_pattern',
            'is_featured', 'is_active', 'max_attendees', 'registration_required',
            'registration_link', 'meeting_url', 'meta_title', 'meta_description',
        ];

        if ($event) {
            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            $event->update($updateData);
        } else {
            $createData = ['created_by' => $data['created_by']];
            if (isset($data['church_id'])) {
                $createData['church_id'] = $data['church_id'];
            }
            foreach ($fields as $field) {
                if (array_key_exists($field, $data)) {
                    $createData[$field] = $data[$field];
                }
            }
            $event = Event::create($createData);
        }

        return $event;
    }
}
