<?php

namespace App\Plugins\LiveMeeting\Policies;

use App\Models\User;
use App\Plugins\LiveMeeting\Models\Meeting;
use Common\Core\BasePolicy;

class MeetingPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('live_meeting.view');
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->hasPermission('live_meeting.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('live_meeting.create');
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $meeting->host_id === $user->id
            || $user->hasPermission('live_meeting.update');
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $meeting->host_id === $user->id
            || $user->hasPermission('live_meeting.delete');
    }
}
