<?php

namespace Plugins\Analytics\Models;

use App\Models\Church;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url', 'user_id', 'session_id',
        'ip_hash', 'user_agent', 'referrer',
        'church_id', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function scopeForPeriod(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        return $churchId
            ? $query->where('church_id', $churchId)
            : $query;
    }
}
