<?php

namespace App\Plugins\Groups\Models;

use App\Plugins\Timeline\Models\Post;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_featured' => 'boolean',
        'member_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $group->slug = Str::slug($group->name) . '-' . Str::random(6);
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\GroupFactory::new();
    }

    // --- Relationships ---

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->members()->where('status', 'approved');
    }

    public function pendingMembers(): HasMany
    {
        return $this->members()->where('status', 'pending');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    // --- Membership helpers ---

    public function getMembership(int $userId): ?GroupMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    public function isApprovedMember(int $userId): bool
    {
        return $this->approvedMembers()->where('user_id', $userId)->exists();
    }

    public function isGroupAdmin(int $userId): bool
    {
        return $this->approvedMembers()
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->exists();
    }

    public function isGroupAdminOrModerator(int $userId): bool
    {
        return $this->approvedMembers()
            ->where('user_id', $userId)
            ->whereIn('role', ['admin', 'moderator'])
            ->exists();
    }

    // --- Scopes ---

    public function scopePubliclyVisible($query)
    {
        return $query->whereIn('type', ['public', 'church_only']);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // --- Counter cache ---

    public function refreshMemberCount(): void
    {
        $this->update([
            'member_count' => $this->approvedMembers()->count(),
        ]);
    }
}
