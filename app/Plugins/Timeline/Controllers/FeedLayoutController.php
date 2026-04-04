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
     * Get all feed layouts for the authenticated user's church
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $churchId = $user->church_id;

        $layouts = FeedLayout::forChurch($churchId)
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
     * Get the active feed layout for the church
     */
    public function active(Request $request): JsonResponse
    {
        $user = $request->user();
        $churchId = $user->church_id;

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
            // Create default layout if none exists
            $layout = $this->createDefaultLayout($churchId);
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
        $data['church_id'] = $user->church_id;

        // If this is being set as active, deactivate other layouts
        if ($data['is_active'] ?? false) {
            FeedLayout::forChurch($user->church_id)
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

        // If this is being set as active, deactivate other layouts
        if (($data['is_active'] ?? false) && !$layout->is_active) {
            FeedLayout::forChurch($layout->church_id)
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
        $layoutCount = FeedLayout::forChurch($layout->church_id)->count();
        if ($layoutCount <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last feed layout.',
            ], 422);
        }

        // If deleting active layout, activate another one
        if ($layout->is_active) {
            $nextLayout = FeedLayout::forChurch($layout->church_id)
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
     * Create a default layout for a church
     */
    protected function createDefaultLayout($churchId): FeedLayout
    {
        $layout = FeedLayout::create([
            'church_id' => $churchId,
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
     * Format widget instance for frontend
     */
    protected function formatWidgetInstance(FeedWidgetInstance $instance): array
    {
        return [
            'id' => $instance->id,
            'widget_key' => $instance->widget->widget_key,
            'display_name' => $instance->widget->display_name,
            'component_path' => $instance->widget->component_path,
            'icon' => $instance->widget->icon,
            'position' => $instance->position,
            'config' => $instance->getMergedConfig(),
            'styling' => $instance->getStyling(),
            'is_visible' => $instance->is_visible,
            'is_collapsible' => $instance->is_collapsible,
            'is_collapsed' => $instance->is_collapsed,
            'responsive_behavior' => $instance->getResponsiveBehavior(),
        ];
    }
}