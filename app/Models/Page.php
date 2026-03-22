<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'content', 'template',
        'meta_title', 'meta_description',
        'status', 'author_id', 'church_id', 'published_at',
        'builder_data', 'builder_html', 'builder_css', 'use_builder',
    ];

    protected $casts = [
        'use_builder'  => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        if ($churchId) {
            return $query->where('church_id', $churchId);
        }

        return $query->whereNull('church_id');
    }
}
