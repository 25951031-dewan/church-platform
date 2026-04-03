<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'name', 'email', 'phone', 'subject', 'message',
        'is_read', 'read_at', 'admin_reply', 'reply_message', 'replied_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function scopeUnread($query) { return $query->where('is_read', false); }
}
