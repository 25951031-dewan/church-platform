<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Church extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'logo',
        'cover_image',
        'status',
        'is_featured',
        'is_verified',
        'followers_count',
        'members_count',
        'social_links',
        'custom_pages',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'is_featured'    => 'boolean',
        'is_verified'    => 'boolean',
        'social_links'   => 'array',
        'custom_pages'   => 'array',
        'settings'       => 'array',
        'followers_count' => 'integer',
        'members_count'  => 'integer',
        'deleted_at'     => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function churchMembers(): HasMany
    {
        return $this->hasMany(ChurchMember::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChurchMember::class)->where('type', 'member');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(ChurchMember::class)->where('type', 'follow');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
