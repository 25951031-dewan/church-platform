<?php

namespace Common\Chat\Models;

use Common\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = ['type', 'name', 'created_by'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all users/participants in this conversation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot(['last_read_at', 'is_muted', 'joined_at'])
            ->using(ConversationUser::class);
    }

    /**
     * Get all messages in this conversation (newest first).
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest message for this conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * Get the user who created this conversation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get unread message count for a specific user.
     */
    public function unreadCountFor(User $user): int
    {
        $pivot = $this->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot;

        $lastRead = $pivot?->last_read_at;

        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))
            ->count();
    }

    /**
     * Check if a user is a participant in this conversation.
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Get display name for this conversation (for a specific viewer).
     */
    public function getDisplayNameFor(User $viewer): string
    {
        if ($this->type === 'group') {
            return $this->name ?? 'Group Chat';
        }

        // For direct conversations, show the other user's name
        $otherUser = $this->users->where('id', '!=', $viewer->id)->first();
        return $otherUser?->name ?? 'Unknown User';
    }
}
