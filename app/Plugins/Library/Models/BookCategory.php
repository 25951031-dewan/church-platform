<?php

namespace App\Plugins\Library\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\BookCategoryFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (BookCategory $category) {
            if (empty($category->slug)) {
                $slug = Str::slug($category->name);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BookCategory::class, 'parent_id')->orderBy('sort_order');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
