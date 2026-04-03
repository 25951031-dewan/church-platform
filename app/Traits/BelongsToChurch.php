<?php

namespace App\Traits;

use App\Scopes\ChurchScope;
use App\Services\ChurchContext;

trait BelongsToChurch
{
    public static function bootBelongsToChurch(): void
    {
        static::addGlobalScope(new ChurchScope());

        // Auto-set church_id on create in multi-church mode
        static::creating(function ($model) {
            if (
                config('app.church_mode', 'single') === 'multi'
                && empty($model->church_id)
            ) {
                $context = app(ChurchContext::class);
                if ($context->has()) {
                    $model->church_id = $context->getId();
                }
            }
        });
    }

    /**
     * Relationship to the owning church.
     */
    public function church()
    {
        return $this->belongsTo(\App\Models\Church::class);
    }

    /**
     * Local scope: filter for a specific church.
     */
    public function scopeForChurch($query, ?int $churchId)
    {
        return $query->withoutGlobalScope(ChurchScope::class)
                     ->where($this->getTable() . '.church_id', $churchId);
    }

    /**
     * Local scope: remove church filtering (for Super Admin cross-church queries).
     */
    public function scopeAllChurches($query)
    {
        return $query->withoutGlobalScope(ChurchScope::class);
    }
}
