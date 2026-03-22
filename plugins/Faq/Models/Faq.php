<?php

namespace Plugins\Faq\Models;

use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'faq_category_id', 'question', 'answer',
        'sort_order', 'church_id', 'author_id',
        'views_count', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
        'views_count'  => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        return $query->where(function ($q) use ($churchId) {
            $q->whereNull('church_id')->orWhere('church_id', $churchId);
        });
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }
}
