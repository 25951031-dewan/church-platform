<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class MeetingLoader
{
    public function load(Meeting $meeting): Meeting
    {
        return $meeting->load(['host']);
    }

    public function loadForDetail(Meeting $meeting): array
    {
        $this->load($meeting);

        return [
            'meeting' => $meeting,
        ];
    }
}
