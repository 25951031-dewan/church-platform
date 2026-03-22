<?php

namespace App\Core;

use App\Services\PlatformModeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingsManager
{
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL = 3600;

    private array $data = [];

    public function __construct()
    {
        $this->data = $this->load();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now()]
        );

        $this->flush();
    }

    public function all(): array
    {
        return $this->data;
    }

    /**
     * Return the feature toggles map keyed by feature slug.
     * Falls back to all-enabled defaults when no row exists yet.
     *
     * @return array<string, bool>
     */
    public function getFeatureToggles(): array
    {
        $row = DB::table('settings')->where('key', 'platform')->first();

        if (! $row || empty($row->feature_toggles)) {
            return $this->defaultFeatureToggles();
        }

        $stored = json_decode($row->feature_toggles, true);

        // Merge with defaults so new features are enabled automatically
        return array_merge($this->defaultFeatureToggles(), $stored ?? []);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return (bool) ($this->getFeatureToggles()[$feature] ?? true);
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        PlatformModeService::flush();
        $this->data = $this->load();
    }

    private function load(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DB::table('settings')
                ->get()
                ->keyBy('key')
                ->map(fn ($row) => $this->castValue($row->value))
                ->toArray();
        });
    }

    private function castValue(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function defaultFeatureToggles(): array
    {
        return [
            'announcement'  => true,
            'verse'         => true,
            'blessing'      => true,
            'prayer'        => true,
            'blog'          => true,
            'events'        => true,
            'library'       => true,
            'bible_studies' => true,
            'testimony'     => true,
            'galleries'     => true,
            'ministries'    => true,
            'sermons'       => true,
            'reviews'       => true,
            'hymns'         => true,
            'bible_reader'  => true,
        ];
    }
}
