<?php

namespace Plugins\Post\Models;

use App\Models\Church;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Plugins\Comment\Models\Comment;
use Plugins\Community\Models\Community;
use Plugins\Reaction\Models\Reaction;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    protected $table = 'social_posts';

    protected $fillable = [
        'user_id', 'church_id', 'community_id', 'parent_id',
        'type', 'body', 'media', 'meta',
        'is_anonymous', 'status', 'published_at',
        'is_pinned', 'is_approved', 'approved_by',
    ];

    protected $casts = [
        'media' => 'array',
        'meta' => 'array',
        'is_anonymous' => 'boolean',
        'published_at' => 'datetime',
        'shares_count' => 'integer',
        'is_pinned' => 'boolean',
        'approved_by' => 'integer',
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

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isReshare(): bool
    {
        return $this->parent_id !== null;
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->whereNull('parent_id')->latest();
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    const TYPES = ['post', 'prayer', 'blessing', 'poll', 'bible_study'];

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function isPoll(): bool
    {
        return $this->type === 'poll';
    }

    public function isPrayerAnswered(): bool
    {
        return $this->type === 'prayer' && ($this->meta['answered'] ?? false);
    }
}
