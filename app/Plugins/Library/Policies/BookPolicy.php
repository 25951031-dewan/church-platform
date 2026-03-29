<?php

namespace App\Plugins\Library\Policies;

use App\Models\User;
use App\Plugins\Library\Models\Book;
use Common\Core\BasePolicy;

class BookPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('library.view');
    }

    public function view(?User $user, Book $book): bool
    {
        if ($book->is_active) {
            return $user ? $user->hasPermission('library.view') : false;
        }
        if ($user && $book->isOwnedBy($user->id)) {
            return true;
        }
        return $user ? $user->hasPermission('library.update') : false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('library.create');
    }

    public function update(User $user, Book $book): bool
    {
        return $user->hasPermission('library.update');
    }

    public function delete(User $user, Book $book): bool
    {
        return $user->hasPermission('library.delete');
    }

    // library.read permission is seeded but not gated here yet —
    // it will gate the online PDF.js reader when that feature is added.

    public function download(User $user, Book $book): bool
    {
        if (!$book->is_active) {
            return false;
        }
        return $user->hasPermission('library.download');
    }

    public function manageCategories(User $user): bool
    {
        return $user->hasPermission('library.manage_categories');
    }
}
