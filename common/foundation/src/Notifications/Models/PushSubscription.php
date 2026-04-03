<?php

namespace Common\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushSubscription extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'player_id',
        'device_type',
        'device_name',
        'last_active_at',
    ];

    protected $casts = [
        'last_active_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Available device types.
     */
    public const DEVICE_TYPES = [
        'web' => 'Web Browser',
        'ios' => 'iOS App',
        'android' => 'Android App',
    ];

    /**
     * Update last active timestamp.
     */
    public function updateLastActive(): self
    {
        $this->update(['last_active_at' => now()]);
        return $this;
    }

    /**
     * Check if subscription is recent.
     */
    public function isRecent(): bool
    {
        if (!$this->last_active_at) {
            return false;
        }

        return $this->last_active_at->diffInDays(now()) < 30;
    }

    // Scopes

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->where('last_active_at', '>=', now()->subDays(30));
    }

    public function scopeByDeviceType($query, string $type)
    {
        return $query->where('device_type', $type);
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
