<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'email', 'name', 'is_active', 'token', 'subscribed_at', 'unsubscribed_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($subscriber) {
            if (!$subscriber->token) {
                $subscriber->token = Str::random(64);
            }
        });
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
