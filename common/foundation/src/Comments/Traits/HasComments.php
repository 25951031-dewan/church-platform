<?php

namespace Common\Comments\Traits;

use Common\Comments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function topLevelComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function commentCount(): int
    {
        return $this->comments()->count();
    }
}
