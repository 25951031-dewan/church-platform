<?php
namespace Plugins\Event\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'church_id', 'community_id', 'created_by', 'title', 'description', 'cover_image',
        'start_at', 'end_at', 'location', 'latitude', 'longitude', 'is_online', 'meeting_url',
        'is_recurring', 'recurrence_rule', 'recurrence_parent_id', 'category',
        'max_attendees', 'going_count', 'maybe_count', 'status', 'reminder_sent_at',
    ];

    protected $casts = [
        'start_at'         => 'datetime',
        'end_at'           => 'datetime',
        'reminder_sent_at' => 'datetime',
        'is_online'        => 'boolean',
        'is_recurring'     => 'boolean',
    ];

    /**
     * Virtual attribute — true when start and end are on different calendar dates.
     */
    public function getIsMultiDayAttribute(): bool
    {
        return Carbon::parse($this->start_at)->toDateString()
            !== Carbon::parse($this->end_at)->toDateString();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(EventAttendee::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_at', '<', now());
    }

    protected static function newFactory()
    {
        return \Database\Factories\EventFactory::new();
    }
}
