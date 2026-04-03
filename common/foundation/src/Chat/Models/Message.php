<?php

namespace Common\Chat\Models;

use Common\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = ['conversation_id', 'user_id', 'body', 'type', 'file_entry_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $with = ['user'];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this message was sent by a specific user.
     */
    public function isSentBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get a preview of the message body (for conversation lists).
     */
    public function getPreview(int $length = 50): string
    {
        if ($this->type !== 'text') {
            return match ($this->type) {
                'image' => '📷 Image',
                'file' => '📎 File',
                'audio' => '🎵 Audio',
                default => 'Attachment',
            };
        }

        if (!$this->body) {
            return '';
        }

        return strlen($this->body) > $length
            ? substr($this->body, 0, $length) . '...'
            : $this->body;
    }
}
