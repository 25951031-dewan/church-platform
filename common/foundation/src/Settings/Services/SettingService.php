<?php

namespace Common\Settings\Services;

use Common\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'app.settings';
    private const CACHE_TTL = 3600; // 1 hour

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->getAll();
        return $all[$key] ?? $default;
    }

    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::pluck('value', 'key')->toArray();
        });
    }

    public function getByGroup(string $prefix): array
    {
        $all = $this->getAll();
        return collect($all)
            ->filter(fn ($v, $k) => str_starts_with($k, $prefix . '.'))
            ->toArray();
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->clearCache();
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
