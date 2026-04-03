<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Testimony extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'name', 'slug', 'born_again_date', 'baptism_date', 'testimony', 'excerpt',
        'featured_image', 'status', 'is_featured', 'view_count', 'published_at',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'user_id',
    ];

    protected $casts = [
        'born_again_date' => 'date',
        'baptism_date' => 'date',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeApproved($query) { return $query->whereIn('status', ['approved', 'featured']); }
    public function scopeFeatured($query) { return $query->approved()->where('is_featured', true); }

    public function incrementView() { $this->increment('view_count'); }
}
