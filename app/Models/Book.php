<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Book extends Model
{
    use BelongsToChurch;

    protected static function booted()
    {
        static::creating(function ($book) {
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
    protected $fillable = [
        'church_id', 'title', 'slug', 'author', 'description', 'cover_image', 'pdf_file',
        'category', 'isbn', 'publisher', 'pages', 'published_year',
        'download_count', 'view_count', 'is_featured', 'is_active', 'is_free', 'is_published',
        'tags', 'meta_title', 'meta_description', 'uploaded_by',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function setPublishYearAttribute($value)
    {
        $this->attributes['published_year'] = $value;
    }

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function incrementDownload() { $this->increment('download_count'); }
    public function incrementView() { $this->increment('view_count'); }
}
