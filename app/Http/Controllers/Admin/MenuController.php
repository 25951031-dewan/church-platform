<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Get all menu items and settings
     */
    public function index(Request $request): JsonResponse
    {
        $churchId = $request->user()->church_id;

        // Get menu items organized by location
        $menuItems = MenuItem::forChurch($churchId)
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('location')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('location');

        $settings = MenuSettings::forChurch($churchId)->first() ?? new MenuSettings();

        return response()->json([
            'menu_items' => [
                'header' => $menuItems->get('header', collect()),
                'footer' => $menuItems->get('footer', collect()),
                'mobile' => $menuItems->get('mobile', collect()),
                'admin' => $menuItems->get('admin', collect()),
            ],
            'settings' => [
                'header_style' => $settings->header_style ?? 'default',
                'mobile_menu_style' => $settings->mobile_menu_style ?? 'hamburger',
                'show_search' => $settings->show_search ?? true,
                'show_login' => $settings->show_login ?? true,
                'show_register' => $settings->show_register ?? true,
                'sticky_header' => $settings->sticky_header ?? false,
                'mega_menu_enabled' => $settings->mega_menu_enabled ?? false,
            ],
        ]);
    }

    /**
     * Create a new menu item
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|string|in:header,footer,mobile,admin',
            'title' => 'required|string|max:100',
            'url' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'target' => 'nullable|string|in:_self,_blank',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'visibility_rules' => 'nullable|array',
            'mega_menu_content' => 'nullable|string',
            'css_class' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $data = $request->validated();
        $data['church_id'] = $churchId;

        // Set sort order if not provided
        if (!isset($data['sort_order'])) {
            $maxOrder = MenuItem::forChurch($churchId)
                ->where('location', $data['location'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order') ?? 0;
            $data['sort_order'] = $maxOrder + 1;
        }

        $menuItem = MenuItem::create($data);

        return response()->json([
            'success' => true,
            'menu_item' => $menuItem->load('children'),
            'message' => 'Menu item created successfully.',
        ], 201);
    }

    /**
     * Update a menu item
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorize('update', $menuItem);

        $validator = Validator::make($request->all(), [
            'location' => 'sometimes|string|in:header,footer,mobile,admin',
            'title' => 'sometimes|string|max:100',
            'url' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:50',
            'target' => 'nullable|string|in:_self,_blank',
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'visibility_rules' => 'nullable|array',
            'mega_menu_content' => 'nullable|string',
            'css_class' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->validated();

        // Prevent circular references
        if (isset($data['parent_id']) && $this->wouldCreateCircularReference($menuItem, $data['parent_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set parent - would create circular reference.',
            ], 422);
        }

        $menuItem->update($data);

        return response()->json([
            'success' => true,
            'menu_item' => $menuItem->fresh(['children']),
            'message' => 'Menu item updated successfully.',
        ]);
    }

    /**
     * Delete a menu item
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        $this->authorize('delete', $menuItem);

        // Delete children first
        $menuItem->children()->delete();
        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu item deleted successfully.',
        ]);
    }

    /**
     * Reorder menu items
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|string|in:header,footer,mobile,admin',
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:menu_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|integer|exists:menu_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $location = $request->location;

        foreach ($request->items as $itemData) {
            MenuItem::where('id', $itemData['id'])
                ->where('church_id', $churchId)
                ->where('location', $location)
                ->update([
                    'sort_order' => $itemData['sort_order'],
                    'parent_id' => $itemData['parent_id'] ?? null,
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu items reordered successfully.',
        ]);
    }

    /**
     * Update menu settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'header_style' => 'nullable|string|in:default,centered,sidebar,minimal',
            'mobile_menu_style' => 'nullable|string|in:hamburger,bottom_tabs,slide_over',
            'show_search' => 'nullable|boolean',
            'show_login' => 'nullable|boolean',
            'show_register' => 'nullable|boolean',
            'sticky_header' => 'nullable|boolean',
            'mega_menu_enabled' => 'nullable|boolean',
            'custom_css' => 'nullable|string|max:10000',
            'custom_js' => 'nullable|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $data = $request->validated();
        $data['church_id'] = $churchId;

        $settings = MenuSettings::updateOrCreate(
            ['church_id' => $churchId],
            $data
        );

        return response()->json([
            'success' => true,
            'settings' => $settings,
            'message' => 'Menu settings updated successfully.',
        ]);
    }

    /**
     * Get available menu locations and their descriptions
     */
    public function locations(): JsonResponse
    {
        $locations = [
            'header' => [
                'name' => 'Header Menu',
                'description' => 'Main navigation in the site header',
                'max_depth' => 3,
                'supports_mega_menu' => true,
            ],
            'footer' => [
                'name' => 'Footer Menu',
                'description' => 'Navigation links in the site footer',
                'max_depth' => 2,
                'supports_mega_menu' => false,
            ],
            'mobile' => [
                'name' => 'Mobile Menu',
                'description' => 'Mobile-optimized navigation',
                'max_depth' => 2,
                'supports_mega_menu' => false,
            ],
            'admin' => [
                'name' => 'Admin Menu',
                'description' => 'Administrative interface navigation',
                'max_depth' => 3,
                'supports_mega_menu' => false,
            ],
        ];

        return response()->json([
            'locations' => $locations,
        ]);
    }

    /**
     * Get available routes for menu linking
     */
    public function availableRoutes(): JsonResponse
    {
        $routes = [
            'public' => [
                'home' => 'Home Page',
                'about' => 'About Us',
                'services' => 'Our Services',
                'events' => 'Events',
                'sermons' => 'Sermons',
                'blog' => 'Blog',
                'prayer-requests' => 'Prayer Requests',
                'contact' => 'Contact Us',
            ],
            'auth' => [
                'login' => 'Login',
                'register' => 'Register',
                'profile' => 'My Profile',
                'dashboard' => 'Dashboard',
            ],
            'admin' => [
                'admin.dashboard' => 'Admin Dashboard',
                'admin.users' => 'User Management',
                'admin.settings' => 'Settings',
                'admin.plugins' => 'Plugin Management',
            ],
        ];

        return response()->json([
            'routes' => $routes,
        ]);
    }

    /**
     * Generate menu preview
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|string|in:header,footer,mobile,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $churchId = $request->user()->church_id;
        $location = $request->location;

        $menuItems = MenuItem::forChurch($churchId)
            ->where('location', $location)
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order');
            }])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'preview' => [
                'location' => $location,
                'items' => $menuItems,
                'count' => $menuItems->count(),
            ],
        ]);
    }

    /**
     * Check if setting a parent would create a circular reference
     */
    private function wouldCreateCircularReference(MenuItem $item, ?int $newParentId): bool
    {
        if (!$newParentId || $newParentId === $item->id) {
            return false;
        }

        $parent = MenuItem::find($newParentId);
        while ($parent) {
            if ($parent->id === $item->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}