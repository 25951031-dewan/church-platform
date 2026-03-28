<?php

namespace App\Plugins\Sermons\Policies;

use App\Models\User;
use App\Plugins\Sermons\Models\Sermon;
use Common\Core\BasePolicy;

class SermonPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sermons.view');
    }

    public function view(User $user, Sermon $sermon): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sermons.create');
    }

    public function update(User $user, Sermon $sermon): bool
    {
        if ($sermon->isOwnedBy($user->id)) {
            return $user->hasPermission('sermons.update');
        }
        return $user->hasPermission('sermons.update_any');
    }

    public function delete(User $user, Sermon $sermon): bool
    {
        if ($sermon->isOwnedBy($user->id)) {
            return $user->hasPermission('sermons.delete');
        }
        return $user->hasPermission('sermons.delete_any');
    }

    public function manageSeries(User $user): bool
    {
        return $user->hasPermission('sermons.manage_series');
    }

    public function manageSpeakers(User $user): bool
    {
        return $user->hasPermission('sermons.manage_speakers');
    }
}
