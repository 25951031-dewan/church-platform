<?php

namespace App\Plugins\Sermons\Models;

use Common\Comments\Traits\HasComments;
use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Sermon extends Model
{
    use HasReactions, HasComments, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sermon_date' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'view_count' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sermon $sermon) {
            if (empty($sermon->slug)) {
                $slug = Str::slug($sermon->title);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $sermon->slug = $slug;
            }
        });
    }

    protected static function newFactory()
    {
        return \Database\Factories\SermonFactory::new();
    }

    // --- Relationships ---

    public function author(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'author_id');
    }

    public function sermonSeries(): BelongsTo
    {
        return $this->belongsTo(SermonSeries::class, 'series_id');
    }

    public function speakerProfile(): BelongsTo
    {
        return $this->belongsTo(Speaker::class, 'speaker_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->active();
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->author_id === $userId;
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    public function hasAudio(): bool
    {
        return !empty($this->audio_url);
    }

    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }
}
