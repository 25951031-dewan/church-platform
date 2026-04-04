<?php

namespace Common\Chat\Models;

use Common\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id', 'user_id', 'body', 'type', 
        'file_entry_id', 'reply_to_id', 'is_edited', 'edited_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_edited' => 'boolean',
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
     * Get the message this is a reply to.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Get replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    /**
     * Get reactions on this message.
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * Get read receipts for this message.
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    /**
     * Check if this message was sent by a specific user.
     */
    public function isSentBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Check if message was read by a specific user.
     */
    public function wasReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * Get reaction counts grouped by emoji.
     */
    public function getReactionCounts(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }

    /**
     * Add a reaction from a user.
     */
    public function addReaction(User $user, string $emoji): MessageReaction
    {
        return $this->reactions()->firstOrCreate([
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);
    }

    /**
     * Remove a reaction from a user.
     */
    public function removeReaction(User $user, string $emoji): bool
    {
        return $this->reactions()
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->delete() > 0;
    }

    /**
     * Edit the message body.
     */
    public function edit(string $newBody): void
    {
        $this->update([
            'body' => $newBody,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
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
