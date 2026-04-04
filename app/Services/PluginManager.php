<?php

namespace App\Services;

use App\Contracts\PluginInterface;
use App\Models\PluginStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class PluginManager
{
    /**
     * Cache key for plugin metadata.
     */
    const CACHE_KEY = 'plugins.discovered';

    /**
     * Path to plugins directory.
     *
     * @var string
     */
    protected $pluginsPath;

    /**
     * Cache TTL in seconds.
     *
     * @var int
     */
    protected $cacheTtl;

    /**
     * Create a new PluginManager instance.
     */
    public function __construct()
    {
        $this->pluginsPath = config('plugins.plugins_path', app_path('Plugins'));
        $this->cacheTtl = config('plugins.cache_ttl', 3600);
    }

    /**
     * Discover all plugins in the plugins directory.
     *
     * @return array
     */
    public function discoverPlugins(): array
    {
        if (config('plugins.cache_enabled', true)) {
            return Cache::remember(self::CACHE_KEY, $this->cacheTtl, function () {
                return $this->scanPlugins();
            });
        }

        return $this->scanPlugins();
    }

    /**
     * Scan the plugins directory for valid plugins.
     *
     * @return array
     */
    protected function scanPlugins(): array
    {
        $plugins = [];

        if (!File::exists($this->pluginsPath)) {
            return $plugins;
        }

        $directories = File::directories($this->pluginsPath);

        foreach ($directories as $directory) {
            $pluginName = basename($directory);
            $manifestPath = $directory . '/Plugin.php';

            if (File::exists($manifestPath)) {
                try {
                    $manifestClass = "App\\Plugins\\{$pluginName}\\Plugin";
                    
                    if (class_exists($manifestClass)) {
                        $manifest = new $manifestClass();
                        
                        if ($manifest instanceof PluginInterface) {
                            $plugins[$pluginName] = [
                                'name' => $manifest->getName(),
                                'version' => $manifest->getVersion(),
                                'description' => $manifest->getDescription(),
                                'dependencies' => $manifest->getDependencies(),
                                'path' => $directory,
                                'manifest' => $manifestClass,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // Skip plugins that fail to load
                    \Log::error("Failed to load plugin {$pluginName}: " . $e->getMessage());
                }
            } else {
                // Plugin without manifest - include basic info
                $plugins[$pluginName] = [
                    'name' => $pluginName,
                    'version' => '1.0.0',
                    'description' => "Plugin {$pluginName}",
                    'dependencies' => [],
                    'path' => $directory,
                    'manifest' => null,
                ];
            }
        }

        return $plugins;
    }

    /**
     * Enable a plugin.
     *
     * @param string $name
     * @return bool
     */
    public function enablePlugin(string $name): bool
    {
        $plugins = $this->discoverPlugins();

        if (!isset($plugins[$name])) {
            return false;
        }

        $status = PluginStatus::firstOrNew(['name' => $name]);
        $status->enabled = true;
        $status->version = $plugins[$name]['version'];
        $status->metadata = $plugins[$name];
        $status->save();

        // Clear cache to refresh enabled plugins
        $this->clearCache();

        return true;
    }

    /**
     * Disable a plugin.
     *
     * @param string $name
     * @return bool
     */
    public function disablePlugin(string $name): bool
    {
        $status = PluginStatus::where('name', $name)->first();

        if (!$status) {
            return false;
        }

        $status->enabled = false;
        $status->save();

        // Clear cache to refresh enabled plugins
        $this->clearCache();

        return true;
    }

    /**
     * Get all enabled plugins.
     *
     * @return array
     */
    public function getEnabledPlugins(): array
    {
        $allPlugins = $this->discoverPlugins();
        
        // If table doesn't exist yet, return all plugins as enabled
        try {
            if (!\Schema::hasTable('plugin_status')) {
                return $allPlugins;
            }
            
            $enabledStatuses = PluginStatus::where('enabled', true)->pluck('name')->toArray();
        } catch (\Exception $e) {
            // If any database error, return all plugins as enabled
            return $allPlugins;
        }

        // If no plugin status records exist, enable all discovered plugins by default
        if (empty($enabledStatuses)) {
            return $allPlugins;
        }

        return array_filter($allPlugins, function ($pluginName) use ($enabledStatuses) {
            return in_array($pluginName, $enabledStatuses);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Load routes from enabled plugins.
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $enabledPlugins = $this->getEnabledPlugins();

        foreach ($enabledPlugins as $pluginName => $pluginData) {
            $this->loadPluginRoutes($pluginName, $pluginData);
        }
    }

    /**
     * Load routes for a specific plugin.
     *
     * @param string $pluginName
     * @param array $pluginData
     * @return void
     */
    protected function loadPluginRoutes(string $pluginName, array $pluginData): void
    {
        $routesPath = $pluginData['path'] . '/Routes';

        if (!File::exists($routesPath)) {
            return;
        }

        // Load api.php if exists
        $apiRoutesPath = $routesPath . '/api.php';
        if (File::exists($apiRoutesPath)) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/v1')
                ->group($apiRoutesPath);
        }

        // Load public.php if exists (for public routes)
        $publicRoutesPath = $routesPath . '/public.php';
        if (File::exists($publicRoutesPath)) {
            Route::middleware(['api'])
                ->prefix('api/v1')
                ->group($publicRoutesPath);
        }

        // Load web.php if exists (for web routes)
        $webRoutesPath = $routesPath . '/web.php';
        if (File::exists($webRoutesPath)) {
            Route::middleware(['web'])
                ->group($webRoutesPath);
        }

        // Load admin.php if exists (for admin routes)
        $adminRoutesPath = $routesPath . '/admin.php';
        if (File::exists($adminRoutesPath)) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/v1')
                ->group($adminRoutesPath);
        }

        // Boot plugin if manifest exists
        if ($pluginData['manifest']) {
            try {
                $manifest = new $pluginData['manifest']();
                if ($manifest instanceof PluginInterface) {
                    $manifest->boot();
                }
            } catch (\Exception $e) {
                \Log::error("Failed to boot plugin {$pluginName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clear the plugin cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Check if a plugin is enabled.
     *
     * @param string $name
     * @return bool
     */
    public function isPluginEnabled(string $name): bool
    {
        $enabledPlugins = $this->getEnabledPlugins();
        return isset($enabledPlugins[$name]);
    }

    /**
     * Get plugin metadata.
     *
     * @param string $name
     * @return array|null
     */
    public function getPlugin(string $name): ?array
    {
        $plugins = $this->discoverPlugins();
        return $plugins[$name] ?? null;
    }
}
