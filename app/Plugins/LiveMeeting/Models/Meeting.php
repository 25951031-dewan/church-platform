<?php

namespace App\Plugins\LiveMeeting\Models;

use App\Models\User;
use App\Models\Church;
use App\Plugins\Events\Models\Event;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'event_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_participants' => 'integer',
        'requires_registration' => 'boolean',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(MeetingRegistration::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_registrations')
            ->withPivot(['registered_at', 'attended', 'attended_at']);
    }

    public function isUserRegistered(int $userId): bool
    {
        return $this->registrations()->where('user_id', $userId)->exists();
    }

    public function isFull(): bool
    {
        if (!$this->max_participants) {
            return false;
        }

        return $this->registrations()->count() >= $this->max_participants;
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
