<?php

namespace App\Plugins\Events\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_recurring' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'registration_required' => 'boolean',
        'max_attendees' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $slug = Str::slug($event->title);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $event->slug = $slug;
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\EventFactory::new();
    }

    // --- Relationships ---

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function attendingRsvps(): HasMany
    {
        return $this->rsvps()->where('status', 'attending');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(\App\Models\EventRegistration::class);
    }

    // --- RSVP helpers ---

    public function getUserRsvp(int $userId): ?EventRsvp
    {
        return $this->rsvps()->where('user_id', $userId)->first();
    }

    public function rsvpCounts(): array
    {
        return $this->rsvps()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    // --- Scopes ---

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now())
            ->where('is_active', true)
            ->orderBy('start_date');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->created_by === $userId;
    }

    public function isFull(): bool
    {
        if (!$this->max_attendees) return false;
        return $this->attendingRsvps()->count() >= $this->max_attendees;
    }

    public function isPast(): bool
    {
        $endOrStart = $this->end_date ?? $this->start_date;
        return $endOrStart->isPast();
    }
}
