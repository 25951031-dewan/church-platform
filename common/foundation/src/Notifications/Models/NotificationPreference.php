<?php

namespace Common\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'push_enabled',
        'email_enabled',
        'sms_enabled',
        'in_app_enabled',
    ];

    protected $casts = [
        'push_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
    ];

    /**
     * Available notification types.
     */
    public const TYPES = [
        'sermon' => 'New Sermons',
        'prayer' => 'Prayer Requests',
        'event' => 'Events & Reminders',
        'group' => 'Group Posts',
        'chat' => 'Chat Messages',
        'meeting' => 'Live Meetings',
        'member' => 'New Members',
        'reaction' => 'Reactions',
        'comment' => 'Comments',
    ];

    /**
     * Default channel configuration for each notification type.
     */
    public static function defaults(): array
    {
        return [
            'sermon' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
            'prayer' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
            'event' => ['push' => true, 'email' => true, 'sms' => true, 'in_app' => true],
            'group' => ['push' => true, 'email' => false, 'sms' => false, 'in_app' => true],
            'chat' => ['push' => true, 'email' => false, 'sms' => false, 'in_app' => true],
            'meeting' => ['push' => true, 'email' => true, 'sms' => false, 'in_app' => true],
            'member' => ['push' => false, 'email' => true, 'sms' => false, 'in_app' => true],
            'reaction' => ['push' => false, 'email' => false, 'sms' => false, 'in_app' => true],
            'comment' => ['push' => true, 'email' => false, 'sms' => false, 'in_app' => true],
        ];
    }

    /**
     * Get user's preferences for a specific notification type.
     */
    public static function getForUser(int $userId, string $type): self
    {
        $preference = static::where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();

        if (!$preference) {
            $defaults = static::defaults()[$type] ?? [];
            $preference = static::create([
                'user_id' => $userId,
                'notification_type' => $type,
                'push_enabled' => $defaults['push'] ?? true,
                'email_enabled' => $defaults['email'] ?? true,
                'sms_enabled' => $defaults['sms'] ?? false,
                'in_app_enabled' => $defaults['in_app'] ?? true,
            ]);
        }

        return $preference;
    }

    /**
     * Check if a specific channel is enabled for this preference.
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'push', 'onesignal' => $this->push_enabled,
            'email', 'mail' => $this->email_enabled,
            'sms', 'twilio' => $this->sms_enabled,
            'database', 'in_app' => $this->in_app_enabled,
            default => false,
        };
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
