<?php

namespace App\Plugins\Timeline\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Timeline\Models\FeedLayout;
use App\Plugins\Timeline\Models\FeedWidget;
use App\Plugins\Timeline\Models\FeedWidgetInstance;
use App\Plugins\Timeline\Requests\CreateFeedLayoutRequest;
use App\Plugins\Timeline\Requests\UpdateFeedLayoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedLayoutController extends Controller
{
    /**
     * Get all feed layouts (single-church mode — no church_id filtering)
     */
    public function index(Request $request): JsonResponse
    {
        $layouts = FeedLayout::withoutGlobalScopes()
            ->with(['widgetInstances.widget'])
            ->orderBy('is_active', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'layouts' => $layouts,
        ]);
    }

    /**
     * Get the active feed layout
     */
    public function active(Request $request): JsonResponse
    {
        $layout = FeedLayout::withoutGlobalScopes()
            ->active()
            ->with([
                'widgetInstances' => function ($query) {
                    $query->visible()->ordered();
                },
                'widgetInstances.widget'
            ])
            ->first();

        if (!$layout) {
            // Create default layout if none exists
            $layout = $this->createDefaultLayout();
        }

        // Format the layout for frontend consumption
        $formattedLayout = [
            'id' => $layout->id,
            'name' => $layout->name,
            'layout_data' => $layout->layout_data,
            'mobile_config' => $layout->mobile_config,
            'responsive_settings' => $layout->responsive_settings,
            'panes' => [
                'left' => [
                    'config' => $layout->left_sidebar_config,
                    'widgets' => $layout->getWidgetsForPane('left')->get()->map(function ($instance) {
                        return $this->formatWidgetInstance($instance);
                    }),
                ],
                'center' => [
                    'config' => $layout->getPaneConfig('center'),
                    'widgets' => $layout->getWidgetsForPane('center')->get()->map(function ($instance) {
                        return $this->formatWidgetInstance($instance);
                    }),
                ],
                'right' => [
                    'config' => $layout->right_sidebar_config,
                    'widgets' => $layout->getWidgetsForPane('right')->get()->map(function ($instance) {
                        return $this->formatWidgetInstance($instance);
                    }),
                ],
            ],
        ];

        return response()->json([
            'layout' => $formattedLayout,
        ]);
    }

    /**
     * Create a new feed layout
     */
    public function store(CreateFeedLayoutRequest $request): JsonResponse
    {
        $user = $request->user();
        
        // Ensure user has permission to create layouts
        $this->authorize('create', FeedLayout::class);

        $data = $request->validated();

        // If this is being set as active, deactivate all other layouts
        if ($data['is_active'] ?? false) {
            FeedLayout::withoutGlobalScopes()
                ->where('is_active', true)
                ->update(['is_active' => false]);
        }

        $layout = FeedLayout::create($data);

        // Create default widget instances if this is a new layout
        if (empty($data['widget_instances'])) {
            FeedWidgetInstance::createDefaultInstances($layout);
        }

        $layout->load(['widgetInstances.widget']);

        return response()->json([
            'layout' => $layout,
            'message' => 'Feed layout created successfully.',
        ], 201);
    }

    /**
     * Show a specific feed layout
     */
    public function show(Request $request, FeedLayout $layout): JsonResponse
    {
        $this->authorize('view', $layout);

        $layout->load(['widgetInstances.widget']);

        return response()->json([
            'layout' => $layout,
        ]);
    }

    /**
     * Update a feed layout
     */
    public function update(UpdateFeedLayoutRequest $request, FeedLayout $layout): JsonResponse
    {
        $this->authorize('update', $layout);

        $data = $request->validated();

        // If this is being set as active, deactivate all other layouts
        if (($data['is_active'] ?? false) && !$layout->is_active) {
            FeedLayout::withoutGlobalScopes()
                ->where('is_active', true)
                ->where('id', '!=', $layout->id)
                ->update(['is_active' => false]);
        }

        $layout->update($data);

        // Update widget instances if provided
        if (isset($data['widget_instances'])) {
            $this->updateWidgetInstances($layout, $data['widget_instances']);
        }

        $layout->load(['widgetInstances.widget']);

        return response()->json([
            'layout' => $layout,
            'message' => 'Feed layout updated successfully.',
        ]);
    }

    /**
     * Delete a feed layout
     */
    public function destroy(Request $request, FeedLayout $layout): JsonResponse
    {
        $this->authorize('delete', $layout);

        // Prevent deletion of the only layout
        $layoutCount = FeedLayout::withoutGlobalScopes()->count();
        if ($layoutCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last feed layout.',
            ], 422);
        }

        // If deleting active layout, activate another one
        if ($layout->is_active) {
            $nextLayout = FeedLayout::withoutGlobalScopes()
                ->where('id', '!=', $layout->id)
                ->first();
            
            if ($nextLayout) {
                $nextLayout->update(['is_active' => true]);
            }
        }

        $layout->delete();

        return response()->json([
            'message' => 'Feed layout deleted successfully.',
        ]);
    }

    /**
     * Update widget instances for a layout
     */
    protected function updateWidgetInstances(FeedLayout $layout, array $instances): void
    {
        // Remove existing instances
        $layout->widgetInstances()->delete();

        // Create new instances
        foreach ($instances as $instanceData) {
            FeedWidgetInstance::create([
                'layout_id' => $layout->id,
                'widget_id' => $instanceData['widget_id'],
                'pane' => $instanceData['pane'],
                'position' => $instanceData['position'] ?? 0,
                'config' => $instanceData['config'] ?? null,
                'styling' => $instanceData['styling'] ?? null,
                'is_visible' => $instanceData['is_visible'] ?? true,
                'is_collapsible' => $instanceData['is_collapsible'] ?? true,
                'is_collapsed' => $instanceData['is_collapsed'] ?? false,
                'responsive_behavior' => $instanceData['responsive_behavior'] ?? null,
            ]);
        }
    }

    /**
     * Create the default global feed layout
     */
    protected function createDefaultLayout(): FeedLayout
    {
        $layout = FeedLayout::create([
            'church_id' => null,
            'name' => 'Main Feed',
            'is_active' => true,
            'layout_data' => FeedLayout::getDefaultLayoutData(),
            'left_sidebar_config' => [
                'width' => '280px',
                'collapsible' => true,
                'default_collapsed' => false,
            ],
            'right_sidebar_config' => [
                'width' => '280px',
                'collapsible' => true,
                'default_collapsed' => false,
            ],
            'mobile_config' => [
                'hide_sidebars' => true,
                'show_hamburger_menu' => true,
            ],
        ]);

        FeedWidgetInstance::createDefaultInstances($layout);

        return $layout;
    }

    /**
     * Activate a specific feed layout
     */
    public function activate(Request $request, FeedLayout $layout): JsonResponse
    {
        $this->authorize('update', $layout);

        // Deactivate all other layouts for this church
        FeedLayout::forChurch($layout->church_id)
            ->where('is_active', true)
            ->where('id', '!=', $layout->id)
            ->update(['is_active' => false]);

        // Activate this layout
        $layout->update(['is_active' => true]);

        return response()->json([
            'layout' => $layout->fresh(['widgetInstances.widget']),
            'message' => 'Feed layout activated successfully.',
        ]);
    }

    /**
     * Duplicate a feed layout
     */
    public function duplicate(Request $request, FeedLayout $layout): JsonResponse
    {
        $this->authorize('create', FeedLayout::class);
        $this->authorize('view', $layout);

        $newLayout = $layout->replicate();
        $newLayout->name = $layout->name . ' (Copy)';
        $newLayout->is_active = false;
        $newLayout->save();

        // Duplicate widget instances
        foreach ($layout->widgetInstances as $instance) {
            $newInstance = $instance->replicate();
            $newInstance->layout_id = $newLayout->id;
            $newInstance->save();
        }

        $newLayout->load(['widgetInstances.widget']);

        return response()->json([
            'layout' => $newLayout,
            'message' => 'Feed layout duplicated successfully.',
        ], 201);
    }

    /**
     * Get active layout for public consumption (no auth required)
     */
    public function publicActive(Request $request): JsonResponse
    {
        // Get church ID from query parameter or default church
        $churchId = $request->query('church_id', 1); // Default to church ID 1

        $layout = FeedLayout::forChurch($churchId)
            ->active()
            ->with([
                'widgetInstances' => function ($query) {
                    $query->visible()->ordered();
                },
                'widgetInstances.widget'
            ])
            ->first();

        if (!$layout) {
            return response()->json([
                'layout' => null,
                'message' => 'No active layout found for this church.',
            ]);
        }

        // Format for public consumption (minimal data)
        $publicLayout = [
            'id' => $layout->id,
            'name' => $layout->name,
            'layout_data' => $layout->layout_data,
            'mobile_config' => $layout->mobile_config,
            'widgets' => $layout->widgetInstances->map(function ($instance) {
                return [
                    'widget_key' => $instance->widget->widget_key,
                    'display_name' => $instance->widget->display_name,
                    'component_path' => $instance->widget->component_path,
                    'pane' => $instance->pane,
                    'position' => $instance->position,
                    'config' => $instance->getMergedConfig(),
                    'is_visible' => $instance->is_visible,
                ];
            }),
        ];

        return response()->json([
            'layout' => $publicLayout,
        ]);
    }

    /**
     * Format widget instance for API response
     */
    protected function formatWidgetInstance($instance): array
    {
        return [
            'id' => $instance->id,
            'widget_key' => $instance->widget?->widget_key ?? $instance->widget_key ?? 'unknown',
            'display_name' => $instance->widget?->display_name ?? $instance->display_name ?? 'Widget',
            'pane' => $instance->pane,
            'sort_order' => $instance->position ?? $instance->sort_order ?? 0,
            'is_visible' => $instance->is_visible ?? true,
            'config' => $instance->config ?? [],
            'styling' => $instance->styling ?? [],
        ];
    }
}