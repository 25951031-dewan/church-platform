<?php

namespace App\Plugins\Prayer\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrayerRequest extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_public' => 'boolean',
        'is_anonymous' => 'boolean',
        'is_urgent' => 'boolean',
        'pastoral_flag' => 'boolean',
    ];

    public const CATEGORIES = [
        'health', 'family', 'financial', 'spiritual',
        'relationships', 'work', 'grief', 'other',
    ];

    public const STATUSES = ['pending', 'approved', 'praying', 'answered'];

    protected static function newFactory()
    {
        return \Database\Factories\PrayerRequestFactory::new();
    }

    // --- Relationships ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function flaggedByUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'flagged_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(PrayerUpdate::class)->orderByDesc('created_at');
    }

    // --- Scopes ---

    public function scopePublicWall($query)
    {
        return $query->where('is_public', true)
            ->where('status', '!=', 'pending')
            ->orderByDesc('is_urgent')
            ->latest();
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'praying', 'answered']);
    }

    public function scopeFlagged($query)
    {
        return $query->where('pastoral_flag', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->user_id === $userId;
    }

    public function prayerCount(): int
    {
        return $this->reactions()->where('type', 'pray')->count();
    }

    public function userHasPrayed(int $userId): bool
    {
        return $this->reactions()
            ->where('user_id', $userId)
            ->where('type', 'pray')
            ->exists();
    }
}
