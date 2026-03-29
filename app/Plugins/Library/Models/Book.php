<?php

namespace App\Plugins\Library\Models;

use Common\Reactions\Traits\HasReactions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasReactions, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'pages_count' => 'integer',
        'view_count' => 'integer',
        'download_count' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\BookFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Book $book) {
            if (empty($book->slug)) {
                $slug = Str::slug($book->title);
                $original = $slug;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $book->slug = $slug;
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'uploaded_by');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(\App\Plugins\ChurchBuilder\Models\Church::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function isOwnedBy(int $userId): bool
    {
        return $this->uploaded_by === $userId;
    }

    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    public function incrementDownload(): void
    {
        $this->increment('download_count');
    }

    public function hasPdf(): bool
    {
        return !empty($this->pdf_path);
    }
}
