<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Ministry extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'name', 'slug', 'description', 'content', 'image', 'leader_name',
        'leader_photo', 'meeting_schedule', 'contact_email', 'is_active', 'sort_order'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query) { return $query->where('is_active', true)->orderBy('sort_order'); }
}
