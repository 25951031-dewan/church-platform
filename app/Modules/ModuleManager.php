<?php

namespace App\Modules;

use Illuminate\Support\Facades\Cache;

class ModuleManager
{
    protected array $modules = [];
    protected array $config = [];

    public function __construct()
    {
        $this->config = config('modules', []);
    }

    /**
     * Load module states from settings or config defaults.
     */
    public function loadModules(): void
    {
        $this->modules = Cache::store('file')->remember('module_states', 300, function () {
            $stored = [];
            try {
                $setting = \App\Models\Setting::first();
                if ($setting && !empty($setting->module_config)) {
                    $stored = is_array($setting->module_config)
                        ? $setting->module_config
                        : json_decode($setting->module_config, true) ?? [];
                }
            } catch (\Throwable $e) {
                // DB might not be ready during migration
            }

            $resolved = [];
            foreach ($this->config as $key => $meta) {
                $resolved[$key] = $stored[$key]['enabled'] ?? $meta['default'] ?? true;
            }
            return $resolved;
        });
    }

    /**
     * Check if a module is active.
     */
    public function isActive(string $module): bool
    {
        if (empty($this->modules)) {
            $this->loadModules();
        }
        return $this->modules[$module] ?? ($this->config[$module]['default'] ?? true);
    }

    /**
     * Get all module configurations.
     */
    public function all(): array
    {
        if (empty($this->modules)) {
            $this->loadModules();
        }
        $result = [];
        foreach ($this->config as $key => $meta) {
            $result[$key] = array_merge($meta, ['enabled' => $this->modules[$key] ?? true]);
        }
        return $result;
    }

    /**
     * Enable a module and clear cache.
     */
    public function enable(string $module): void
    {
        $this->modules[$module] = true;
        $this->persistAndClearCache();
    }

    /**
     * Disable a module and clear cache.
     */
    public function disable(string $module): void
    {
        $this->modules[$module] = false;
        $this->persistAndClearCache();
    }

    protected function persistAndClearCache(): void
    {
        Cache::store('file')->forget('module_states');
        try {
            $setting = \App\Models\Setting::first();
            if ($setting) {
                $existing = is_array($setting->module_config)
                    ? $setting->module_config
                    : json_decode($setting->module_config ?? '{}', true) ?? [];
                foreach ($this->modules as $key => $enabled) {
                    $existing[$key] = array_merge($existing[$key] ?? [], ['enabled' => $enabled]);
                }
                $setting->update(['module_config' => json_encode($existing)]);
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
    }
}
