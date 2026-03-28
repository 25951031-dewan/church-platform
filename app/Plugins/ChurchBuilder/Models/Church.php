<?php

namespace App\Plugins\ChurchBuilder\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Church extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'service_hours' => 'array',
        'documents' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'verified_at' => 'datetime',
    ];

    protected $appends = ['logo_url', 'cover_photo_url'];

    protected static function newFactory()
    {
        return \Database\Factories\ChurchFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Church $church) {
            if (empty($church->slug)) {
                $slug = Str::slug($church->name);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $church->slug = $slug;
            }
        });
    }

    // --- Relationships ---

    public function admin(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'admin_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'verified_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChurchMember::class);
    }

    public function approvedMembers(): HasMany
    {
        return $this->hasMany(ChurchMember::class)->where('status', 'approved');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ChurchPage::class)->orderBy('sort_order');
    }

    public function publishedPages(): HasMany
    {
        return $this->hasMany(ChurchPage::class)
            ->where('is_published', true)
            ->orderBy('sort_order');
    }

    // --- Scopes ---

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 50)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }

    // --- Accessors ---

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }

    // --- Helpers ---

    public function isOwnedBy(int $userId): bool
    {
        return $this->admin_user_id === $userId || $this->created_by === $userId;
    }

    public function isChurchAdmin(int $userId): bool
    {
        return $this->admin_user_id === $userId
            || $this->members()->where('user_id', $userId)->where('role', 'admin')->exists();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->where('status', 'approved')->exists();
    }

    public function getMembership(int $userId): ?ChurchMember
    {
        return $this->members()->where('user_id', $userId)->first();
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }
}
