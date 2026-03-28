<?php

namespace Common\Comments\Policies;

use App\Models\User;
use Common\Comments\Models\Comment;
use Common\Core\BasePolicy;

class CommentPolicy extends BasePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermission('comments.create');
    }

    public function update(User $user, Comment $comment): bool
    {
        if ($comment->isOwnedBy($user->id)) {
            return $user->hasPermission('comments.update');
        }
        return false;
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->isOwnedBy($user->id)) {
            return true;
        }
        return $user->hasPermission('comments.delete_any');
    }
}
