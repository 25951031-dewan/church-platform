<?php

namespace Plugins\Entity\Models;

use App\Models\User;
use Database\Factories\ChurchEntityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ChurchEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'church_entities';

    protected $fillable = [
        'type', 'owner_id', 'name', 'slug', 'description', 'category_id',
        'cover_image', 'profile_image', 'website', 'address', 'phone',
        'social_links', 'action_button', 'is_verified', 'privacy',
        'allow_posts', 'require_approval', 'parent_entity_id',
        'members_count', 'posts_count', 'meta', 'is_active',
        'verification_requested_at',
    ];

    protected $casts = [
        'social_links' => 'array',
        'action_button' => 'array',
        'meta' => 'array',
        'is_verified' => 'boolean',
        'allow_posts' => 'boolean',
        'require_approval' => 'boolean',
        'is_active' => 'boolean',
        'members_count'             => 'integer',
        'posts_count'               => 'integer',
        'verification_requested_at' => 'datetime',
    ];

    // Scopes
    public function scopePages($query)
    {
        return $query->where('type', 'page');
    }

    public function scopeCommunities($query)
    {
        return $query->where('type', 'community');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Relations
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EntityMember::class, 'entity_id');
    }

    public function approvedMembers(): HasMany
    {
        return $this->hasMany(EntityMember::class, 'entity_id')->where('status', 'approved');
    }

    public function admins(): HasMany
    {
        return $this->hasMany(EntityMember::class, 'entity_id')
            ->whereIn('role', ['admin', 'moderator'])
            ->where('status', 'approved');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChurchEntity::class, 'parent_entity_id');
    }

    public function subPages(): HasMany
    {
        return $this->hasMany(ChurchEntity::class, 'parent_entity_id');
    }

    // Helpers
    public function generateSlug(): string
    {
        return Str::slug($this->name);
    }

    public function isMember(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->exists();
    }

    public function isAdmin(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'moderator'])
            ->where('status', 'approved')
            ->exists();
    }

    protected static function newFactory()
    {
        return ChurchEntityFactory::new();
    }
}
