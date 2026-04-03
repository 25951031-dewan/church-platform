<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'gallery_id', 'image_path', 'caption', 'sort_order'];

    public function gallery() { return $this->belongsTo(Gallery::class); }
}
