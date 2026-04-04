<?php

namespace Common\Chat\Models;

use Common\Auth\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinnedMessage extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['conversation_id', 'message_id', 'pinned_by'];

    protected $casts = [
        'pinned_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PinnedMessage $pin) {
            $pin->pinned_at = now();
        });
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function pinnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }
}
