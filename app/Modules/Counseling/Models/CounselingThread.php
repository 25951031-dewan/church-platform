<?php

namespace App\Modules\Counseling\Models;

use App\Models\User;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounselingThread extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'user_id', 'counselor_id', 'subject',
        'status', 'priority', 'is_anonymous',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CounselingMessage::class, 'thread_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(CounselingMessage::class, 'thread_id')->latestOfMany();
    }

    public function scopeForParticipant($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('counselor_id', $userId);
        });
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function isParticipant(int $userId): bool
    {
        return $this->user_id === $userId || $this->counselor_id === $userId;
    }
}
