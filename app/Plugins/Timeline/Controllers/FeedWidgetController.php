<?php

namespace App\Plugins\Timeline\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\Timeline\Models\FeedWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedWidgetController extends Controller
{
    /**
     * Get all available feed widgets
     */
    public function index(Request $request): JsonResponse
    {
        $widgets = FeedWidget::available()
            ->ordered()
            ->get()
            ->map(function ($widget) {
                return [
                    'id' => $widget->id,
                    'widget_key' => $widget->widget_key,
                    'display_name' => $widget->display_name,
                    'description' => $widget->description,
                    'category' => $widget->category,
                    'icon' => $widget->icon,
                    'component_path' => $widget->component_path,
                    'config_schema' => $widget->config_schema,
                    'default_config' => $widget->default_config,
                    'permissions_required' => $widget->permissions_required,
                    'is_system' => $widget->is_system,
                    'is_customizable' => $widget->is_customizable,
                    'preview_image' => $widget->preview_image,
                ];
            });

        return response()->json([
            'widgets' => $widgets,
        ]);
    }

    /**
     * Get widgets grouped by category
     */
    public function categories(Request $request): JsonResponse
    {
        $widgets = FeedWidget::available()
            ->ordered()
            ->get()
            ->groupBy('category');

        $categories = $widgets->map(function ($categoryWidgets, $category) {
            return [
                'name' => $category,
                'display_name' => ucfirst(str_replace('_', ' ', $category)),
                'widgets' => $categoryWidgets->map(function ($widget) {
                    return [
                        'id' => $widget->id,
                        'widget_key' => $widget->widget_key,
                        'display_name' => $widget->display_name,
                        'description' => $widget->description,
                        'icon' => $widget->icon,
                        'is_system' => $widget->is_system,
                        'is_customizable' => $widget->is_customizable,
                        'preview_image' => $widget->preview_image,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Get detailed widget information with configuration schema
     */
    public function show(Request $request, FeedWidget $widget): JsonResponse
    {
        $this->authorize('view', $widget);

        $widgetData = [
            'id' => $widget->id,
            'widget_key' => $widget->widget_key,
            'display_name' => $widget->display_name,
            'description' => $widget->description,
            'category' => $widget->category,
            'icon' => $widget->icon,
            'component_path' => $widget->component_path,
            'config_schema' => $widget->getConfigSchema(),
            'default_config' => $widget->getDefaultConfig(),
            'styling_schema' => $widget->getStylingSchema(),
            'default_styling' => $widget->getDefaultStyling(),
            'permissions_required' => $widget->getPermissionsRequired(),
            'is_system' => $widget->is_system,
            'is_customizable' => $widget->is_customizable,
            'responsive_options' => $widget->getResponsiveOptions(),
            'preview_image' => $widget->preview_image,
            'documentation_url' => $widget->documentation_url,
            'version' => $widget->version,
        ];

        return response()->json([
            'widget' => $widgetData,
        ]);
    }

    /**
     * Validate widget configuration
     */
    public function validateConfig(Request $request, FeedWidget $widget): JsonResponse
    {
        $config = $request->input('config', []);
        $styling = $request->input('styling', []);

        $validation = $widget->validateConfiguration($config, $styling);

        return response()->json([
            'valid' => $validation['valid'],
            'errors' => $validation['errors'] ?? [],
            'warnings' => $validation['warnings'] ?? [],
        ]);
    }

    /**
     * Get widget preview data (for testing widget configurations)
     */
    public function preview(Request $request, FeedWidget $widget): JsonResponse
    {
        $this->authorize('view', $widget);

        $config = $request->input('config', []);
        $styling = $request->input('styling', []);

        // Validate the configuration first
        $validation = $widget->validateConfiguration($config, $styling);
        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Invalid widget configuration.',
                'errors' => $validation['errors'],
            ], 422);
        }

        // Generate preview data based on widget type
        $previewData = $this->generatePreviewData($widget, $config);

        return response()->json([
            'widget' => [
                'id' => $widget->id,
                'widget_key' => $widget->widget_key,
                'display_name' => $widget->display_name,
                'component_path' => $widget->component_path,
                'config' => array_merge($widget->getDefaultConfig(), $config),
                'styling' => array_merge($widget->getDefaultStyling(), $styling),
            ],
            'preview_data' => $previewData,
        ]);
    }

    /**
     * Generate sample data for widget preview
     */
    protected function generatePreviewData(FeedWidget $widget, array $config): array
    {
        switch ($widget->widget_key) {
            case 'daily_verse':
                return [
                    'verse' => [
                        'text' => 'For I know the plans I have for you, declares the Lord, plans to prosper you and not to harm you, to give you hope and a future.',
                        'reference' => 'Jeremiah 29:11',
                        'translation' => 'NIV',
                    ],
                ];

            case 'post_feed':
                return [
                    'posts' => [
                        [
                            'id' => 1,
                            'content' => 'Join us for our Sunday service at 10 AM. Looking forward to worshipping together!',
                            'user' => ['name' => 'Pastor John', 'avatar' => null],
                            'created_at' => now()->subHours(2)->toISOString(),
                            'reactions_count' => 12,
                            'comments_count' => 3,
                        ],
                        [
                            'id' => 2,
                            'content' => 'Prayer request: Please pray for our youth mission trip next week. Thank you!',
                            'user' => ['name' => 'Sarah M.', 'avatar' => null],
                            'created_at' => now()->subHours(5)->toISOString(),
                            'reactions_count' => 8,
                            'comments_count' => 1,
                        ],
                    ],
                ];

            case 'announcements':
                return [
                    'announcements' => [
                        [
                            'id' => 1,
                            'title' => 'Church Picnic This Saturday',
                            'content' => 'Join us for food, games, and fellowship at Central Park from 11 AM to 3 PM.',
                            'priority' => 'high',
                            'expires_at' => now()->addDays(3)->toISOString(),
                        ],
                        [
                            'id' => 2,
                            'title' => 'New Bible Study Group',
                            'content' => 'Starting next Wednesday at 7 PM. All are welcome!',
                            'priority' => 'normal',
                            'expires_at' => now()->addWeeks(2)->toISOString(),
                        ],
                    ],
                ];

            case 'events':
                return [
                    'events' => [
                        [
                            'id' => 1,
                            'title' => 'Sunday Worship Service',
                            'start_time' => now()->next('Sunday')->setTime(10, 0)->toISOString(),
                            'location' => 'Main Sanctuary',
                            'attendees_count' => 45,
                        ],
                        [
                            'id' => 2,
                            'title' => 'Youth Group Meeting',
                            'start_time' => now()->next('Friday')->setTime(19, 0)->toISOString(),
                            'location' => 'Youth Room',
                            'attendees_count' => 12,
                        ],
                    ],
                ];

            case 'prayer_requests':
                return [
                    'prayers' => [
                        [
                            'id' => 1,
                            'title' => 'Healing for family member',
                            'is_anonymous' => true,
                            'prayers_count' => 15,
                            'created_at' => now()->subDays(1)->toISOString(),
                        ],
                        [
                            'id' => 2,
                            'title' => 'Job search guidance',
                            'is_anonymous' => false,
                            'author' => 'Mike T.',
                            'prayers_count' => 8,
                            'created_at' => now()->subDays(2)->toISOString(),
                        ],
                    ],
                ];

            default:
                return [
                    'message' => 'Preview data not available for this widget type.',
                ];
        }
    }
}