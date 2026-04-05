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

        // Plugin metadata with descriptions
        $pluginMeta = [
            'timeline' => ['display_name' => 'Timeline', 'description' => 'Community feed with posts, reactions, and comments', 'icon' => 'MessageSquare'],
            'groups' => ['display_name' => 'Groups', 'description' => 'Create and manage community groups', 'icon' => 'Users'],
            'events' => ['display_name' => 'Events', 'description' => 'Church events calendar and registration', 'icon' => 'Calendar'],
            'sermons' => ['display_name' => 'Sermons', 'description' => 'Sermon library with audio/video support', 'icon' => 'Mic'],
            'prayer' => ['display_name' => 'Prayer Requests', 'description' => 'Prayer wall and request management', 'icon' => 'Heart'],
            'chat' => ['display_name' => 'Chat', 'description' => 'Real-time messaging between members', 'icon' => 'MessageCircle'],
            'library' => ['display_name' => 'Library', 'description' => 'Books, documents, and resources', 'icon' => 'BookOpen'],
            'church_builder' => ['display_name' => 'Church Builder', 'description' => 'Build and customize church websites', 'icon' => 'Building'],
            'blog' => ['display_name' => 'Blog', 'description' => 'Articles and blog posts', 'icon' => 'FileText'],
            'live_meeting' => ['display_name' => 'Live Meetings', 'description' => 'Video conferencing and live streams', 'icon' => 'Video'],
            'giving' => ['display_name' => 'Giving', 'description' => 'Online donations and tithing', 'icon' => 'DollarSign'],
            'volunteers' => ['display_name' => 'Volunteers', 'description' => 'Volunteer scheduling and management', 'icon' => 'UserCheck'],
            'fundraising' => ['display_name' => 'Fundraising', 'description' => 'Campaigns and fundraising goals', 'icon' => 'Target'],
            'stories' => ['display_name' => 'Stories', 'description' => 'Share testimonies and stories', 'icon' => 'Film'],
            'pastoral' => ['display_name' => 'Pastoral Care', 'description' => 'Member care and counseling notes', 'icon' => 'HeartHandshake'],
            'marketplace' => ['display_name' => 'Marketplace', 'description' => 'Church store and merchandise', 'icon' => 'ShoppingBag'],
        ];

        $plugins = collect($all)->map(fn($config, $name) => [
            'name'        => $name,
            'display_name' => $pluginMeta[$name]['display_name'] ?? ucfirst(str_replace('_', ' ', $name)),
            'description' => $pluginMeta[$name]['description'] ?? 'No description available',
            'icon'        => $pluginMeta[$name]['icon'] ?? 'Puzzle',
            'version'     => $config['version'] ?? '1.0.0',
            'is_enabled'  => in_array($name, $enabled),
            'has_settings' => file_exists(app_path("Plugins/" . $this->getPluginDirectory($name) . "/Routes/api.php")),
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

    /**
     * Convert plugin key to directory name (e.g., church_builder -> ChurchBuilder)
     */
    protected function getPluginDirectory(string $name): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public function enable(Request $request): JsonResponse
    {
        $name = $request->validate(['name' => 'required|string'])['name'];

        $all = $this->pluginManager->all();
        if (!isset($all[$name])) {
            return response()->json(['message' => "Plugin '{$name}' not found in plugins.json"], 422);
        }

        $this->pluginManager->enable($name);

        // Clear caches — PluginManager uses cache key 'app.plugins' (see common/foundation/src/Core/PluginManager.php)
        \Illuminate\Support\Facades\Cache::forget('app.plugins');

        // Return updated plugin list
        $enabled = $this->pluginManager->getEnabled();
        $plugins = collect($all)->map(fn($config, $name) => [
            'name' => $name,
            'is_enabled' => in_array($name, $enabled),
        ])->values();

        return response()->json([
            'message' => "Plugin '{$name}' enabled",
            'plugins' => $plugins,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $name = $request->validate(['name' => 'required|string'])['name'];

        $all = $this->pluginManager->all();
        if (!isset($all[$name])) {
            return response()->json(['message' => "Plugin '{$name}' not found in plugins.json"], 422);
        }

        $this->pluginManager->disable($name);

        // Clear caches — PluginManager uses cache key 'app.plugins' (see common/foundation/src/Core/PluginManager.php)
        \Illuminate\Support\Facades\Cache::forget('app.plugins');

        // Return updated plugin list
        $enabled = $this->pluginManager->getEnabled();
        $plugins = collect($all)->map(fn($config, $name) => [
            'name' => $name,
            'is_enabled' => in_array($name, $enabled),
        ])->values();

        return response()->json([
            'message' => "Plugin '{$name}' disabled",
            'plugins' => $plugins,
        ]);
    }
}
