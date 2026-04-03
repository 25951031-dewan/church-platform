<?php

namespace App\Plugins\LiveMeeting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingRegistration extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'registered_at',
        'attended',
        'attended_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'attended' => 'boolean',
        'attended_at' => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
