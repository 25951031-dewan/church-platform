<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PluginManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginController extends Controller
{
    public function __construct(
        protected PluginManager $pluginManager
    ) {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(): JsonResponse
    {
        $plugins = $this->pluginManager->discoverPlugins();
        $enabledPlugins = $this->pluginManager->getEnabledPlugins();

        $pluginList = collect($plugins)->map(function ($plugin, $name) use ($enabledPlugins) {
            return [
                'name' => $plugin['name'],
                'version' => $plugin['version'],
                'description' => $plugin['description'],
                'is_enabled' => in_array($name, $enabledPlugins),
                'dependencies' => $plugin['dependencies'],
            ];
        })->values();

        return response()->json([
            'data' => $pluginList,
        ]);
    }

    public function enable(Request $request): JsonResponse
    {
        $name = $request->input('name');
        
        if (!$name) {
            return response()->json(['message' => 'Plugin name required'], 422);
        }

        $this->pluginManager->enablePlugin($name);

        return response()->json([
            'message' => "Plugin '{$name}' enabled",
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $name = $request->input('name');
        
        if (!$name) {
            return response()->json(['message' => 'Plugin name required'], 422);
        }

        $this->pluginManager->disablePlugin($name);

        return response()->json([
            'message' => "Plugin '{$name}' disabled",
        ]);
    }
}
