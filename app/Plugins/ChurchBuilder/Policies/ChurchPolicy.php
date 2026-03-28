<?php

namespace App\Plugins\ChurchBuilder\Policies;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\Church;
use Common\Core\BasePolicy;

class ChurchPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('churches.view');
    }

    public function view(User $user, Church $church): bool
    {
        // Approved churches are visible to all authenticated users
        if ($church->status === 'approved') {
            return true;
        }
        // Own church
        if ($church->isOwnedBy($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.update_any');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('churches.create');
    }

    public function update(User $user, Church $church): bool
    {
        if ($church->isOwnedBy($user->id) || $church->isChurchAdmin($user->id)) {
            return $user->hasPermission('churches.update');
        }
        return $user->hasPermission('churches.update_any');
    }

    public function delete(User $user, Church $church): bool
    {
        if ($church->isOwnedBy($user->id)) {
            return $user->hasPermission('churches.delete');
        }
        return $user->hasPermission('churches.delete_any');
    }

    public function verify(User $user): bool
    {
        return $user->hasPermission('churches.verify');
    }

    public function feature(User $user): bool
    {
        return $user->hasPermission('churches.feature');
    }

    public function manageMembers(User $user, Church $church): bool
    {
        if ($church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_members');
    }

    public function managePages(User $user, Church $church): bool
    {
        if ($church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }
}
