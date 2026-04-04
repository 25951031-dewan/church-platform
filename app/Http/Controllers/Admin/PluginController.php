<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginManager;
use App\Models\PluginSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PluginController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {
        $this->middleware(['auth', 'admin']);
    }

    public function index(): JsonResponse
    {
        $plugins = $this->pluginManager->discoverPlugins();
        $enabledPlugins = $this->pluginManager->getEnabledPlugins();

        $pluginList = collect($plugins)->map(function ($plugin, $name) use ($enabledPlugins) {
            return [
                'name' => $plugin['name'],
                'key' => $name,
                'version' => $plugin['version'],
                'description' => $plugin['description'],
                'author' => $plugin['author'] ?? 'Church Platform',
                'is_enabled' => in_array($name, $enabledPlugins),
                'dependencies' => $plugin['dependencies'],
                'requires_setup' => $plugin['requires_setup'] ?? false,
                'has_settings' => $plugin['has_settings'] ?? false,
                'icon' => $plugin['icon'] ?? 'puzzle-piece',
                'category' => $plugin['category'] ?? 'general',
            ];
        })->values();

        return response()->json([
            'data' => $pluginList,
            'stats' => [
                'total' => $pluginList->count(),
                'enabled' => $pluginList->where('is_enabled', true)->count(),
                'disabled' => $pluginList->where('is_enabled', false)->count(),
            ],
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'force' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $force = $request->input('force', false);

        try {
            // Check dependencies if not forcing
            if (!$force) {
                $plugins = $this->pluginManager->discoverPlugins();
                if (isset($plugins[$name]['dependencies'])) {
                    $missingDeps = $this->checkMissingDependencies($plugins[$name]['dependencies']);
                    if (!empty($missingDeps)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Missing dependencies: ' . implode(', ', $missingDeps),
                            'missing_dependencies' => $missingDeps,
                        ], 422);
                    }
                }
            }

            $this->pluginManager->enablePlugin($name);

            return response()->json([
                'success' => true,
                'message' => "Plugin '{$name}' enabled successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to enable plugin: {$e->getMessage()}",
            ], 500);
        }
    }

    public function disable(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'force' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $force = $request->input('force', false);

        try {
            // Check if other plugins depend on this one
            if (!$force) {
                $dependentPlugins = $this->getDependentPlugins($name);
                if (!empty($dependentPlugins)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot disable - other plugins depend on this: ' . implode(', ', $dependentPlugins),
                        'dependent_plugins' => $dependentPlugins,
                    ], 422);
                }
            }

            $this->pluginManager->disablePlugin($name);

            return response()->json([
                'success' => true,
                'message' => "Plugin '{$name}' disabled successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to disable plugin: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Get plugin settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        $pluginName = $request->route('plugin');
        $churchId = $request->user()->church_id;

        $settings = PluginSettings::where('plugin_name', $pluginName)
            ->where('church_id', $churchId)
            ->get()
            ->pluck('value', 'key');

        // Get plugin configuration schema
        $plugins = $this->pluginManager->discoverPlugins();
        $configSchema = $plugins[$pluginName]['config_schema'] ?? [];

        return response()->json([
            'settings' => $settings,
            'schema' => $configSchema,
        ]);
    }

    /**
     * Update plugin settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $pluginName = $request->route('plugin');
        $churchId = $request->user()->church_id;

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $settings = $request->input('settings');

        // Validate settings against plugin schema
        $plugins = $this->pluginManager->discoverPlugins();
        $configSchema = $plugins[$pluginName]['config_schema'] ?? [];

        foreach ($settings as $key => $value) {
            if (isset($configSchema[$key])) {
                $rules = $configSchema[$key]['validation'] ?? 'string';
                $validator = Validator::make(
                    [$key => $value],
                    [$key => $rules]
                );

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid value for setting '{$key}'",
                        'errors' => $validator->errors(),
                    ], 422);
                }
            }
        }

        // Save settings
        foreach ($settings as $key => $value) {
            PluginSettings::updateOrCreate(
                [
                    'plugin_name' => $pluginName,
                    'church_id' => $churchId,
                    'key' => $key,
                ],
                ['value' => $value]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Plugin settings updated successfully',
        ]);
    }

    /**
     * Install a plugin
     */
    public function install(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plugin_file' => 'required|file|mimes:zip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->pluginManager->installPlugin($request->file('plugin_file'));

            return response()->json([
                'success' => true,
                'message' => 'Plugin installed successfully',
                'plugin' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to install plugin: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Uninstall a plugin
     */
    public function uninstall(Request $request): JsonResponse
    {
        $pluginName = $request->route('plugin');

        try {
            $this->pluginManager->uninstallPlugin($pluginName);

            return response()->json([
                'success' => true,
                'message' => "Plugin '{$pluginName}' uninstalled successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to uninstall plugin: {$e->getMessage()}",
            ], 500);
        }
    }

    /**
     * Get plugin information
     */
    public function show(Request $request): JsonResponse
    {
        $pluginName = $request->route('plugin');
        $plugins = $this->pluginManager->discoverPlugins();

        if (!isset($plugins[$pluginName])) {
            return response()->json([
                'success' => false,
                'message' => 'Plugin not found',
            ], 404);
        }

        $plugin = $plugins[$pluginName];
        $enabledPlugins = $this->pluginManager->getEnabledPlugins();

        return response()->json([
            'plugin' => [
                'name' => $plugin['name'],
                'key' => $pluginName,
                'version' => $plugin['version'],
                'description' => $plugin['description'],
                'author' => $plugin['author'] ?? 'Church Platform',
                'is_enabled' => in_array($pluginName, $enabledPlugins),
                'dependencies' => $plugin['dependencies'],
                'requires_setup' => $plugin['requires_setup'] ?? false,
                'has_settings' => $plugin['has_settings'] ?? false,
                'icon' => $plugin['icon'] ?? 'puzzle-piece',
                'category' => $plugin['category'] ?? 'general',
                'config_schema' => $plugin['config_schema'] ?? [],
                'changelog' => $plugin['changelog'] ?? [],
            ],
        ]);
    }

    /**
     * Check missing dependencies
     */
    private function checkMissingDependencies(array $dependencies): array
    {
        $enabledPlugins = $this->pluginManager->getEnabledPlugins();
        return array_diff($dependencies, $enabledPlugins);
    }

    /**
     * Get plugins that depend on the given plugin
     */
    private function getDependentPlugins(string $pluginName): array
    {
        $plugins = $this->pluginManager->discoverPlugins();
        $enabledPlugins = $this->pluginManager->getEnabledPlugins();
        $dependents = [];

        foreach ($plugins as $name => $plugin) {
            if (in_array($name, $enabledPlugins) && 
                in_array($pluginName, $plugin['dependencies'] ?? [])) {
                $dependents[] = $name;
            }
        }

        return $dependents;
    }
}
