<?php

namespace App\Plugins\Events\Policies;

use App\Models\User;
use App\Plugins\Events\Models\Event;
use Common\Core\BasePolicy;

class EventPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('events.view');
    }

    public function view(User $user, Event $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('events.create');
    }

    public function update(User $user, Event $event): bool
    {
        if ($event->isOwnedBy($user->id)) {
            return $user->hasPermission('events.update');
        }
        return $user->hasPermission('events.update_any');
    }

    public function delete(User $user, Event $event): bool
    {
        if ($event->isOwnedBy($user->id)) {
            return $user->hasPermission('events.delete');
        }
        return $user->hasPermission('events.delete_any');
    }

    public function rsvp(User $user, Event $event): bool
    {
        return $user->hasPermission('events.rsvp');
    }

    public function feature(User $user): bool
    {
        return $user->hasPermission('events.feature');
    }
}
