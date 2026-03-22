<?php

namespace Plugins\Faq\Models;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'church_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'sort_order' => 'integer'];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order');
    }

    public function publishedFaqs(): HasMany
    {
        return $this->faqs()->where('is_published', true);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        return $query->where(function ($q) use ($churchId) {
            $q->whereNull('church_id')->orWhere('church_id', $churchId);
        });
    }
}
