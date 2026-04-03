<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'title', 'slug', 'description', 'cover_image', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function images() { return $this->hasMany(GalleryImage::class)->orderBy('sort_order'); }

    public function scopeActive($query) { return $query->where('is_active', true)->orderBy('sort_order'); }
}
