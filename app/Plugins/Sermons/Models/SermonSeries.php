<?php

namespace App\Plugins\Sermons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SermonSeries extends Model
{
    protected $table = 'sermon_series';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (SermonSeries $series) {
            if (empty($series->slug)) {
                $series->slug = Str::slug($series->name) . '-' . Str::random(6);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class, 'series_id');
    }
}
