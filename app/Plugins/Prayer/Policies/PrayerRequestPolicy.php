<?php

namespace App\Plugins\Prayer\Policies;

use App\Models\User;
use App\Plugins\Prayer\Models\PrayerRequest;
use Common\Core\BasePolicy;

class PrayerRequestPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('prayer.view');
    }

    public function view(User $user, PrayerRequest $prayer): bool
    {
        // Public approved prayers are visible to all authenticated users
        if ($prayer->is_public && $prayer->status !== 'pending') {
            return true;
        }
        // Own prayer
        if ($prayer->isOwnedBy($user->id)) {
            return true;
        }
        // Admins/pastors can see all
        return $user->hasPermission('prayer.view_any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('prayer.create');
    }

    public function update(User $user, PrayerRequest $prayer): bool
    {
        if ($prayer->isOwnedBy($user->id)) {
            return $user->hasPermission('prayer.update');
        }
        return $user->hasPermission('prayer.update_any');
    }

    public function delete(User $user, PrayerRequest $prayer): bool
    {
        if ($prayer->isOwnedBy($user->id)) {
            return $user->hasPermission('prayer.delete');
        }
        return $user->hasPermission('prayer.delete_any');
    }

    public function moderate(User $user): bool
    {
        return $user->hasPermission('prayer.moderate');
    }

    public function flag(User $user): bool
    {
        return $user->hasPermission('prayer.pastoral_flag');
    }

    public function viewAnonymousIdentity(User $user): bool
    {
        return $user->hasPermission('prayer.view_anonymous');
    }
}
