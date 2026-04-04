<?php

namespace App\Plugins\Timeline\Policies;

use App\Models\User;
use App\Plugins\Timeline\Models\FeedLayout;
use Common\Core\BasePolicy;

class FeedLayoutPolicy extends BasePolicy
{
    public function view(User $user, FeedLayout $layout): bool
    {
        return $user->church_id === $layout->church_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.access');
    }

    public function update(User $user, FeedLayout $layout): bool
    {
        return $user->church_id === $layout->church_id
            && $user->hasPermission('admin.access');
    }

    public function delete(User $user, FeedLayout $layout): bool
    {
        return $user->church_id === $layout->church_id
            && $user->hasPermission('admin.access');
    }
}
