<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Church extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'status',
        // General
        'email', 'phone', 'website', 'address', 'city', 'state', 'zip_code',
        'country', 'latitude', 'longitude', 'service_hours', 'denomination', 'year_founded',
        // About
        'short_description', 'history', 'mission_statement', 'vision_statement', 'documents',
        // Appearance
        'logo', 'cover_photo', 'primary_color', 'secondary_color',
        // SEO
        'meta_title', 'meta_description', 'facebook_url', 'instagram_url',
        'youtube_url', 'twitter_url', 'tiktok_url',
        // Admin
        'admin_user_id', 'created_by', 'view_count', 'is_featured',
    ];

    protected $casts = [
        'service_hours' => 'array',
        'documents' => 'array',
        'is_featured' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $appends = ['logo_url', 'cover_photo_url'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo ? asset('storage/' . $this->cover_photo) : null;
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
