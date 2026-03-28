<?php

namespace App\Plugins\Timeline\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasReactions, HasComments;

    protected $guarded = ['id'];

    protected $casts = [
        'is_pinned' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function isPublished(): bool
    {
        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }
        return true;
    }

    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('scheduled_at')
              ->orWhere('scheduled_at', '<=', now());
        });
    }

    public function scopeFeed($query)
    {
        return $query->published()
            ->where('visibility', '!=', 'private')
            ->orderByDesc('is_pinned')
            ->latest();
    }
}
