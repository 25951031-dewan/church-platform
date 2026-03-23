<?php

namespace Plugins\Community\Models;

use App\Models\Church;
use App\Models\User;
use Database\Factories\CommunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): CommunityFactory
    {
        return CommunityFactory::new();
    }

    protected $fillable = [
        'name', 'slug', 'description', 'cover_image',
        'church_id', 'created_by', 'privacy', 'privacy_closed', 'status',
        'members_count', 'posts_count',
        'is_counsel_group', 'requires_approval',
        'counsellor_ids', 'max_members', 'is_anonymous_posting',
    ];

    protected $casts = [
        'is_counsel_group' => 'boolean',
        'requires_approval' => 'boolean',
        'is_anonymous_posting' => 'boolean',
        'privacy_closed' => 'boolean',
        'counsellor_ids' => 'array',
        'members_count' => 'integer',
        'posts_count' => 'integer',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function communityMembers(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->communityMembers()->where('status', 'approved');
    }

    public function pendingMembers(): HasMany
    {
        return $this->communityMembers()->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCounselGroups(Builder $query): Builder
    {
        return $query->where('is_counsel_group', true);
    }

    public function scopeRegularGroups(Builder $query): Builder
    {
        return $query->where('is_counsel_group', false);
    }

    public function isFull(): bool
    {
        return $this->max_members !== null
            && $this->members_count >= $this->max_members;
    }

    public function isCounsellor(int $userId): bool
    {
        return in_array($userId, $this->counsellor_ids ?? [], true);
    }

    public function isClosed(): bool
    {
        return (bool) $this->privacy_closed;
    }

    public function isAdmin(int $userId): bool
    {
        return $this->communityMembers()
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->where('status', 'approved')
            ->exists();
    }
}
