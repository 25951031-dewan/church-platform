<?php

namespace App\Traits;

use App\Scopes\ChurchScope;

trait BelongsToChurch
{
    public static function bootBelongsToChurch(): void
    {
        // ChurchScope is a no-op in single-church mode.
        // church_id is an optional association, not a tenant filter.
        static::addGlobalScope(new ChurchScope());
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
