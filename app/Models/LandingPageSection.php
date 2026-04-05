<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'is_visible',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_visible' => 'boolean',
    ];

    /**
     * Default scope to order by sort_order.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($query) {
            $query->orderBy('sort_order');
        });
    }
}
