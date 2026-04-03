<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Get a setting value, with fallback chain:
     * church-specific → global platform setting → $default
     */
    public function get(string $key, mixed $default = null, ?int $churchId = null): mixed
    {
        // Resolve church ID from context if not specified
        if ($churchId === null && config('app.church_mode', 'single') === 'multi') {
            $context = app(ChurchContext::class);
            $churchId = $context->getId();
        }

        // Try church-specific setting first
        if ($churchId) {
            $churchValue = $this->getChurchSetting($churchId, $key);
            if ($churchValue !== null) {
                return $churchValue;
            }
        }

        // Fall back to global settings
        return $this->getGlobal($key, $default);
    }

    /**
     * Get all global settings (cached).
     */
    public function all(): ?Setting
    {
        return Cache::store('file')->remember('settings_global', 300, function () {
            return Setting::first();
        });
    }

    /**
     * Get a value from the global settings row.
     */
    public function getGlobal(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        if (!$settings) {
            return $default;
        }
        return $settings->$key ?? $default;
    }

    /**
     * Get a per-church setting value.
     */
    public function getChurchSetting(int $churchId, string $key): mixed
    {
        $cacheKey = "settings_church_{$churchId}";
        $churchSettings = Cache::store('file')->remember($cacheKey, 300, function () use ($churchId) {
            return \App\Models\ChurchSetting::where('church_id', $churchId)
                ->pluck('value', 'key')
                ->toArray();
        });

        if (array_key_exists($key, $churchSettings)) {
            $val = $churchSettings[$key];
            return is_string($val) ? json_decode($val, true) ?? $val : $val;
        }

        return null;
    }

    /**
     * Save a per-church setting.
     */
    public function setChurchSetting(int $churchId, string $key, mixed $value): void
    {
        \App\Models\ChurchSetting::updateOrCreate(
            ['church_id' => $churchId, 'key' => $key],
            ['value' => is_array($value) ? $value : $value]
        );
        Cache::store('file')->forget("settings_church_{$churchId}");
    }

    /**
     * Clear global settings cache (call after updating settings).
     */
    public function clearCache(?int $churchId = null): void
    {
        Cache::store('file')->forget('settings_global');
        if ($churchId) {
            Cache::store('file')->forget("settings_church_{$churchId}");
        }
    }
}
