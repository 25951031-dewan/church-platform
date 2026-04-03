<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Sermon extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'title', 'slug', 'description', 'content', 'speaker', 'image', 'thumbnail',
        'video_url', 'audio_url', 'pdf_notes', 'scripture_reference',
        'series', 'category', 'sermon_date', 'duration_minutes', 'duration',
        'view_count', 'is_featured', 'is_active', 'is_published', 'tags',
        'meta_title', 'meta_description', 'author_id',
    ];

    protected $casts = [
        'sermon_date' => 'date',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function author() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }

    public function incrementView() { $this->increment('view_count'); }
}
