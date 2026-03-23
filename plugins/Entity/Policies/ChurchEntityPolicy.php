<?php

namespace Plugins\Entity\Policies;

use App\Models\User;
use Plugins\Entity\Models\ChurchEntity;

class ChurchEntityPolicy
{
    public function update(User $user, ChurchEntity $entity): bool
    {
        return $entity->isAdmin($user->id) || $user->is_admin;
    }

    public function delete(User $user, ChurchEntity $entity): bool
    {
        return $entity->owner_id === $user->id || $user->is_admin;
    }

    public function manageMembers(User $user, ChurchEntity $entity): bool
    {
        return $entity->isAdmin($user->id) || $user->is_admin;
    }
}
