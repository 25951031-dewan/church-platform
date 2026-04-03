<?php

namespace App\Plugins\LiveMeeting\Services;

use App\Plugins\LiveMeeting\Models\Meeting;

class DeleteMeetings
{
    public function execute(array $ids): void
    {
        Meeting::whereIn('id', $ids)->delete();
    }
}
