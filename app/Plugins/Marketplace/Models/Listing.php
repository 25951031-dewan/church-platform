<?php

namespace App\Plugins\Marketplace\Models;

use App\Models\User;
use Common\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Listing extends Model
{
    use HasComments, SoftDeletes;

    protected $table = 'marketplace_listings';

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2',
        'is_negotiable' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'specifications' => 'array',
        'expires_at' => 'datetime',
        'sold_at' => 'datetime',
        'view_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Listing $listing) {
            if (empty($listing->slug)) {
                $slug = Str::slug($listing->title);
                $original = $slug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $counter++;
                }
                $listing->slug = $slug;
            }
        });
    }

    // --- Relationships ---

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'listing_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class, 'listing_id');
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopePriceRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    // --- Helpers ---

    public function markAsSold(): void
    {
        $this->update([
            'status' => 'sold',
            'sold_at' => now(),
        ]);
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }
}
