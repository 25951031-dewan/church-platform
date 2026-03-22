<?php

namespace Plugins\Post\Models;

use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plugins\Community\Models\Community;

class Post extends Model
{
    use SoftDeletes;

    protected $table = 'social_posts';

    protected $fillable = [
        'user_id', 'church_id', 'community_id', 'parent_id',
        'type', 'body', 'media', 'meta',
        'is_anonymous', 'status', 'published_at',
    ];

    protected $casts = [
        'media'        => 'array',
        'meta'         => 'array',
        'is_anonymous' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function reshares(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isReshare(): bool
    {
        return $this->parent_id !== null;
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\Plugins\Comment\Models\Comment::class, 'commentable')
                    ->whereNull('parent_id')->latest();
    }
}
