<?php

namespace App\Plugins\Sermons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Speaker extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (Speaker $speaker) {
            if (empty($speaker->slug)) {
                $speaker->slug = Str::slug($speaker->name) . '-' . Str::random(6);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function sermons(): HasMany
    {
        return $this->hasMany(Sermon::class, 'speaker_id');
    }
}
