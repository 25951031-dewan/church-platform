<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Common\Core\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages plugin enable/disable via config/plugins.json (Common\Core\PluginManager).
 * The Foundation PluginManager is the single source of truth — it's what routes/api.php
 * reads. Copilot's App\Services\PluginManager wrote to a plugin_status DB table that
 * nothing read, so the toggle had no real effect.
 */
class PluginController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {
        $this->middleware(['auth:sanctum', 'permission:admin.access']);
    }

    public function index(): JsonResponse
    {
        $all     = $this->pluginManager->all();
        $enabled = $this->pluginManager->getEnabled();

        $plugins = collect($all)->map(fn($config, $name) => [
            'name'       => $name,
            'version'    => $config['version'] ?? '1.0.0',
            'is_enabled' => in_array($name, $enabled),
        ])->values();

        return response()->json([
            'data' => $plugins,
            'stats' => [
                'total'    => $plugins->count(),
                'enabled'  => $plugins->where('is_enabled', true)->count(),
                'disabled' => $plugins->where('is_enabled', false)->count(),
            ],
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $name = $request->validate(['name' => 'required|string'])['name'];

        $all = $this->pluginManager->all();
        if (!isset($all[$name])) {
            return response()->json(['message' => "Plugin '{$name}' not found in plugins.json"], 422);
        }

        $this->pluginManager->enable($name);

        return response()->json(['message' => "Plugin '{$name}' enabled"]);
    }

    public function disable(Request $request): JsonResponse
    {
        $name = $request->validate(['name' => 'required|string'])['name'];

        $all = $this->pluginManager->all();
        if (!isset($all[$name])) {
            return response()->json(['message' => "Plugin '{$name}' not found in plugins.json"], 422);
        }

        $this->pluginManager->disable($name);

        return response()->json(['message' => "Plugin '{$name}' disabled"]);
    }
}
