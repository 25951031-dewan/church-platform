<?php

namespace App\Plugins\Timeline\Policies;

use App\Models\User;
use App\Plugins\Timeline\Models\Post;
use Common\Core\BasePolicy;

class PostPolicy extends BasePolicy
{
    public function view(User $user, Post $post): bool
    {
        if ($post->visibility === 'public') return true;
        if ($post->visibility === 'members') return $user->hasPermission('posts.view');
        return $post->isOwnedBy($user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.create');
    }

    public function update(User $user, Post $post): bool
    {
        if ($post->isOwnedBy($user->id)) {
            return $user->hasPermission('posts.update');
        }
        return $user->hasPermission('posts.update_any');
    }

    public function delete(User $user, Post $post): bool
    {
        if ($post->isOwnedBy($user->id)) {
            return $user->hasPermission('posts.delete');
        }
        return $user->hasPermission('posts.delete_any');
    }

    public function pin(User $user): bool
    {
        return $user->hasPermission('posts.pin');
    }

    public function announce(User $user): bool
    {
        return $user->hasPermission('posts.announce');
    }
}
