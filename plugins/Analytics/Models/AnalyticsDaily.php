<?php

namespace Plugins\Analytics\Models;

use App\Models\Church;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $fillable = ['date', 'metric', 'value', 'church_id'];

    protected $casts = [
        'date'  => 'date',
        'value' => 'integer',
    ];

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function scopeMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric', $metric);
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        return $churchId
            ? $query->where('church_id', $churchId)
            : $query->whereNull('church_id');
    }

    public function scopeLast(Builder $query, int $days): Builder
    {
        return $query->where('date', '>=', now()->subDays($days)->toDateString());
    }

    /**
     * Upsert a daily metric value (increment by delta).
     */
    public static function increment(string $metric, int $delta, ?int $churchId, string $date): void
    {
        static::upsert(
            [['date' => $date, 'metric' => $metric, 'value' => $delta, 'church_id' => $churchId]],
            ['date', 'metric', 'church_id'],
            ['value' => \Illuminate\Support\Facades\DB::raw("value + {$delta}")]
        );
    }
}
