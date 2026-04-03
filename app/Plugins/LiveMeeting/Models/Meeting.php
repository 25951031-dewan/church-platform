<?php

namespace App\Plugins\LiveMeeting\Models;

use App\Models\User;
use App\Models\Church;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['is_live'];

    protected static function newFactory()
    {
        return \Database\Factories\MeetingFactory::new();
    }

    public function getIsLiveAttribute(): bool
    {
        $now = Carbon::now();
        return $this->starts_at <= $now && $this->ends_at >= $now;
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLive($query)
    {
        $now = Carbon::now();
        return $query->where('starts_at', '<=', $now)->where('ends_at', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>', Carbon::now());
    }
}
