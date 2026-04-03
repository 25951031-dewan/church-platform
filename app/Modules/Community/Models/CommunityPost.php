<?php

namespace App\Modules\Community\Models;

use App\Models\User;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CommunityPost extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'user_id', 'church_id', 'group_id', 'type', 'title', 'body',
        'media', 'is_anonymous', 'status', 'is_pinned',
        'likes_count', 'comments_count', 'shares_count',
    ];

    protected $casts = [
        'media' => 'array',
        'is_anonymous' => 'boolean',
        'is_pinned' => 'boolean',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function scopeTrending($query)
    {
        return $query->orderByRaw(
            '(likes_count * 2 + comments_count * 3 + shares_count) / GREATEST(TIMESTAMPDIFF(HOUR, created_at, NOW()), 1) DESC'
        );
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
