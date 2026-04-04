<?php

namespace App\Plugins\Timeline\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Post extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\PostFactory::new();
    }

    protected $table = 'timeline_posts';

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

    public function group(): BelongsTo
    {
        return $this->belongsTo(\App\Plugins\Groups\Models\Group::class);
    }

    /**
     * Get the channels this post belongs to (BeMusic pattern)
     */
    public function channels(): MorphToMany
    {
        return $this->morphToMany(
            FeedChannel::class,
            'channelable',
            'channel_items',
            'channelable_id',
            'channel_id'
        )->withPivot(['position', 'is_featured', 'metadata'])
         ->withTimestamps();
    }

    /**
     * Add post to a channel
     */
    public function addToChannel(FeedChannel $channel, array $pivotData = []): void
    {
        $this->channels()->syncWithoutDetaching([
            $channel->id => $pivotData
        ]);
    }

    /**
     * Remove post from a channel
     */
    public function removeFromChannel(FeedChannel $channel): void
    {
        $this->channels()->detach($channel->id);
    }

    /**
     * Check if post is in a specific channel
     */
    public function isInChannel(FeedChannel $channel): bool
    {
        return $this->channels()->where('channel_id', $channel->id)->exists();
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

    /**
     * Scope to get posts for specific channels
     */
    public function scopeInChannels($query, array $channelIds)
    {
        return $query->whereHas('channels', function ($q) use ($channelIds) {
            $q->whereIn('channel_id', $channelIds);
        });
    }

    /**
     * Scope to get posts for a specific channel
     */
    public function scopeInChannel($query, $channelId)
    {
        return $query->whereHas('channels', function ($q) use ($channelId) {
            $q->where('channel_id', $channelId);
        });
    }
}
