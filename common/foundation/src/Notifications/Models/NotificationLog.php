<?php

namespace Common\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'notification_id',
        'user_id',
        'channel',
        'status',
        'provider_response',
        'sent_at',
        'delivered_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Available channels.
     */
    public const CHANNELS = [
        'push' => 'Push Notification',
        'email' => 'Email',
        'sms' => 'SMS',
        'database' => 'In-App',
    ];

    /**
     * Available statuses.
     */
    public const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'failed' => 'Failed',
        'bounced' => 'Bounced',
    ];

    /**
     * Mark the log as sent.
     */
    public function markAsSent(?string $response = null): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider_response' => $response,
        ]);

        return $this;
    }

    /**
     * Mark the log as delivered.
     */
    public function markAsDelivered(): self
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the log as failed.
     */
    public function markAsFailed(string $error): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $error,
        ]);

        return $this;
    }

    // Scopes

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'bounced']);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['sent', 'delivered']);
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
