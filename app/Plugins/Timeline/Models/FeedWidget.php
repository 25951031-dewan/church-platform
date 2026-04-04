<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedWidget extends Model
{
    protected $fillable = [
        'widget_key',
        'display_name',
        'description',
        'component_path',
        'icon',
        'category',
        'default_config',
        'schema',
        'is_enabled',
        'requires_auth',
        'permissions',
        'sort_order',
    ];

    protected $casts = [
        'default_config' => 'json',
        'schema' => 'json',
        'permissions' => 'json',
        'is_enabled' => 'boolean',
        'requires_auth' => 'boolean',
    ];

    /**
     * Get the widget instances using this widget
     */
    public function instances(): HasMany
    {
        return $this->hasMany(FeedWidgetInstance::class, 'widget_id');
    }

    /**
     * Check if widget is available for use
     */
    public function isAvailable(): bool
    {
        return $this->is_enabled;
    }

    /**
     * Check if widget requires authentication
     */
    public function requiresAuth(): bool
    {
        return $this->requires_auth;
    }

    /**
     * Get the widget's React component path
     */
    public function getComponentPath(): string
    {
        return $this->component_path;
    }

    /**
     * Get merged configuration (default + instance overrides)
     */
    public function getMergedConfig(array $instanceConfig = []): array
    {
        return array_merge($this->default_config ?? [], $instanceConfig);
    }

    /**
     * Validate configuration against schema
     */
    public function validateConfig(array $config): bool
    {
        if (!$this->schema) {
            return true; // No schema = no validation required
        }

        // TODO: Implement JSON schema validation
        // For now, just return true
        return true;
    }

    /**
     * Scope to get only enabled widgets
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get widgets by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get widgets that don't require authentication
     */
    public function scopePublic($query)
    {
        return $query->where('requires_auth', false);
    }

    /**
     * Get available widget categories
     */
    public static function getCategories(): array
    {
        return [
            'content' => 'Content Widgets',
            'interaction' => 'Interactive Widgets',
            'navigation' => 'Navigation Widgets',
            'custom' => 'Custom Widgets',
        ];
    }

    /**
     * Get default widgets that should be available
     */
    public static function getDefaultWidgets(): array
    {
        return [
            [
                'widget_key' => 'daily_verse',
                'display_name' => 'Daily Verse',
                'description' => 'Display daily Bible verse with reflection',
                'component_path' => 'timeline/widgets/DailyVerseWidget',
                'icon' => 'book-open',
                'category' => 'content',
                'default_config' => [
                    'show_reflection' => true,
                    'show_reference' => true,
                    'translation' => 'NIV',
                ],
            ],
            [
                'widget_key' => 'post_feed',
                'display_name' => 'Post Feed',
                'description' => 'Timeline posts and updates',
                'component_path' => 'timeline/widgets/PostFeedWidget',
                'icon' => 'message-square',
                'category' => 'content',
                'default_config' => [
                    'posts_per_page' => 10,
                    'allow_reactions' => true,
                    'allow_comments' => true,
                ],
            ],
            [
                'widget_key' => 'announcements',
                'display_name' => 'Announcements',
                'description' => 'Church announcements and updates',
                'component_path' => 'timeline/widgets/AnnouncementsWidget',
                'icon' => 'megaphone',
                'category' => 'content',
                'default_config' => [
                    'max_announcements' => 5,
                    'show_dates' => true,
                ],
            ],
            [
                'widget_key' => 'events',
                'display_name' => 'Upcoming Events',
                'description' => 'Display upcoming church events',
                'component_path' => 'timeline/widgets/EventsWidget',
                'icon' => 'calendar',
                'category' => 'content',
                'default_config' => [
                    'days_ahead' => 30,
                    'max_events' => 5,
                ],
            ],
            [
                'widget_key' => 'prayer_requests',
                'display_name' => 'Prayer Requests',
                'description' => 'Community prayer requests',
                'component_path' => 'timeline/widgets/PrayerRequestsWidget',
                'icon' => 'hands-praying',
                'category' => 'interaction',
                'default_config' => [
                    'max_requests' => 5,
                    'allow_anonymous' => true,
                ],
            ],
        ];
    }
}