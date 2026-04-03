<?php

namespace Common\Chat\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected $table = 'conversation_user';

    protected $casts = [
        'last_read_at' => 'datetime',
        'joined_at' => 'datetime',
        'is_muted' => 'boolean',
    ];

    /**
     * Check if this user has muted the conversation.
     */
    public function isMuted(): bool
    {
        return $this->is_muted;
    }

    /**
     * Mark messages as read up to now.
     */
    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }
}
