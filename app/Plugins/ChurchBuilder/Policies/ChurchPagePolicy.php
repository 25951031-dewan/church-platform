<?php

namespace App\Plugins\ChurchBuilder\Policies;

use App\Models\User;
use App\Plugins\ChurchBuilder\Models\ChurchPage;
use Common\Core\BasePolicy;

class ChurchPagePolicy extends BasePolicy
{
    public function view(User $user, ChurchPage $page): bool
    {
        if ($page->is_published) {
            return true;
        }
        return $page->church && $page->church->isChurchAdmin($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('churches.manage_pages');
    }

    public function update(User $user, ChurchPage $page): bool
    {
        if ($page->church && $page->church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }

    public function delete(User $user, ChurchPage $page): bool
    {
        if ($page->church && $page->church->isChurchAdmin($user->id)) {
            return true;
        }
        return $user->hasPermission('churches.manage_pages');
    }
}
