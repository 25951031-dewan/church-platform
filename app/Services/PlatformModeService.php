<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformModeService
{
    private const CACHE_KEY = 'platform_settings';
    private const CACHE_TTL = 3600; // 1 hour

    private array $settings;

    public function __construct()
    {
        $this->settings = $this->loadSettings();
    }

    public function isSingleChurch(): bool
    {
        return $this->settings['platform_mode'] === 'single';
    }

    public function isMultiChurch(): bool
    {
        return $this->settings['platform_mode'] === 'multi';
    }

    public function defaultChurch(): ?int
    {
        return $this->settings['default_church_id'] ?? null;
    }

    public function showChurchDirectory(): bool
    {
        return (bool) ($this->settings['show_church_directory'] ?? false);
    }

    /**
     * Scope a query to the appropriate church context based on platform mode.
     * In single-church mode, restricts to the default church.
     * In multi-church mode, leaves the query unrestricted.
     */
    public function scopeForMode(Builder $query, string $column = 'church_id'): Builder
    {
        if ($this->isSingleChurch() && $this->defaultChurch()) {
            return $query->where($column, $this->defaultChurch());
        }

        return $query;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function loadSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $row = DB::table('settings')->where('key', 'platform')->first();

            if (! $row) {
                return [
                    'platform_mode'         => 'single',
                    'show_church_directory' => false,
                    'feature_toggles'       => [],
                    'default_church_id'     => null,
                ];
            }

            return [
                'platform_mode'         => $row->platform_mode ?? 'single',
                'show_church_directory' => (bool) ($row->show_church_directory ?? false),
                'feature_toggles'       => json_decode($row->feature_toggles ?? '{}', true) ?? [],
                'default_church_id'     => $row->default_church_id ?? null,
            ];
        });
    }
}
