<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'name', 'email', 'rating', 'title', 'content', 'is_approved', 'is_featured', 'user_id'];

    protected $casts = ['is_approved' => 'boolean', 'is_featured' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }

    public function scopeApproved($query) { return $query->where('is_approved', true); }
    public function scopeFeatured($query) { return $query->where('is_featured', true)->where('is_approved', true); }
}
