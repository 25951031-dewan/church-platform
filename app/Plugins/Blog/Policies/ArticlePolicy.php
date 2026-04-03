<?php

namespace App\Plugins\Blog\Policies;

use App\Models\User;
use App\Plugins\Blog\Models\Article;
use Common\Core\BasePolicy;

class ArticlePolicy extends BasePolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // Published articles are public for SEO
    }

    public function view(?User $user, Article $article): bool
    {
        if ($article->status === 'published') {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $article->author_id === $user->id
            || $user->hasPermission('blog.update');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('blog.create');
    }

    public function update(User $user, Article $article): bool
    {
        return $article->author_id === $user->id
            || $user->hasPermission('blog.update');
    }

    public function delete(User $user, Article $article): bool
    {
        if ($article->author_id === $user->id && $article->status === 'draft') {
            return true;
        }

        return $user->hasPermission('blog.delete');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermission('blog.publish');
    }

    public function manageCategories(User $user): bool
    {
        return $user->hasPermission('blog.manage_categories');
    }

    public function manageTags(User $user): bool
    {
        return $user->hasPermission('blog.manage_tags');
    }
}
