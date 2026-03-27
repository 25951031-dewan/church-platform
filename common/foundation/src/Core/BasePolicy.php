<?php

namespace Common\Core;

use App\Models\User;

abstract class BasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Super admins bypass all checks
        if ($user->getRoleLevel() >= 100) {
            return true;
        }
        return null;
    }
}
