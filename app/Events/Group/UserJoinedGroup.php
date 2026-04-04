<?php

namespace App\Events\Group;

use App\Events\BaseEvent;
use App\Plugins\Groups\Models\Group;
use App\Models\User;

class UserJoinedGroup extends BaseEvent
{
    /**
     * The group instance.
     */
    public Group $group;

    /**
     * The user who joined.
     */
    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, User $user)
    {
        parent::__construct($user->id, $group->church_id);
        $this->group = $group;
        $this->user = $user;
    }
}
