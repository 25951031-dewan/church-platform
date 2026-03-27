<?php

namespace Common\Core;

use Illuminate\Support\Facades\Cache;

class PluginManager
{
    private const CACHE_KEY = 'app.plugins';
    private const CACHE_TTL = 300; // 5 minutes

    private string $path;

    public function __construct()
    {
        $this->path = config_path('plugins.json');
    }

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            if (!file_exists($this->path)) {
                return [];
            }
            return json_decode(file_get_contents($this->path), true) ?? [];
        });
    }

    public function isEnabled(string $plugin): bool
    {
        $plugins = $this->all();
        return ($plugins[$plugin]['enabled'] ?? false) === true;
    }

    public function getEnabled(): array
    {
        return collect($this->all())
            ->filter(fn (array $config) => $config['enabled'] === true)
            ->keys()
            ->toArray();
    }

    public function getDisabled(): array
    {
        return collect($this->all())
            ->filter(fn (array $config) => $config['enabled'] === false)
            ->keys()
            ->toArray();
    }

    public function enable(string $plugin): void
    {
        $this->setEnabled($plugin, true);
    }

    public function disable(string $plugin): void
    {
        $this->setEnabled($plugin, false);
    }

    private function setEnabled(string $plugin, bool $enabled): void
    {
        $plugins = $this->all();
        if (!isset($plugins[$plugin])) {
            return;
        }
        $plugins[$plugin]['enabled'] = $enabled;
        file_put_contents($this->path, json_encode($plugins, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        Cache::forget(self::CACHE_KEY);
    }
}
