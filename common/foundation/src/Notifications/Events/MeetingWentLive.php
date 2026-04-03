<?php

namespace Common\Notifications\Events;

use App\Plugins\LiveMeeting\Models\Meeting;

class MeetingWentLive
{
    public function __construct(public Meeting $meeting) {}
}
