<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class BibleStudy extends Model
{
    use BelongsToChurch;

    protected $table = 'bible_studies';

    protected $fillable = [
        'church_id', 'title', 'slug', 'description', 'content', 'image', 'cover_image',
        'category', 'difficulty_level', 'difficulty', 'scripture_reference',
        'video_url', 'audio_url', 'pdf_attachment', 'attachment',
        'duration_minutes', 'view_count', 'is_featured', 'is_active', 'is_published',
        'sort_order', 'author', 'tags', 'meta_title', 'meta_description', 'author_id',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function authorUser() { return $this->belongsTo(User::class, 'author_id'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_active', true); }
    public function scopeByDifficulty($query, $level) { return $query->where('difficulty_level', $level); }

    public function incrementView() { $this->increment('view_count'); }
}
