<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedWidgetInstance extends Model
{
    protected $fillable = [
        'layout_id',
        'widget_id',
        'pane',
        'position',
        'config',
        'styling',
        'is_visible',
        'is_collapsible',
        'is_collapsed',
        'responsive_behavior',
    ];

    protected $casts = [
        'config' => 'json',
        'styling' => 'json',
        'responsive_behavior' => 'json',
        'is_visible' => 'boolean',
        'is_collapsible' => 'boolean',
        'is_collapsed' => 'boolean',
    ];

    /**
     * Get the layout this instance belongs to
     */
    public function layout(): BelongsTo
    {
        return $this->belongsTo(FeedLayout::class, 'layout_id');
    }

    /**
     * Get the widget definition
     */
    public function widget(): BelongsTo
    {
        return $this->belongsTo(FeedWidget::class, 'widget_id');
    }

    /**
     * Get the merged configuration for this instance
     */
    public function getMergedConfig(): array
    {
        $defaultConfig = $this->widget->default_config ?? [];
        $instanceConfig = $this->config ?? [];
        
        return array_merge($defaultConfig, $instanceConfig);
    }

    /**
     * Get the widget's display name
     */
    public function getDisplayName(): string
    {
        return $this->widget->display_name ?? 'Unknown Widget';
    }

    /**
     * Get the widget's component path
     */
    public function getComponentPath(): string
    {
        return $this->widget->component_path ?? '';
    }

    /**
     * Check if this instance is visible
     */
    public function isVisible(): bool
    {
        return $this->is_visible && $this->widget->is_enabled;
    }

    /**
     * Get styling configuration for this instance
     */
    public function getStyling(): array
    {
        return $this->styling ?? [];
    }

    /**
     * Get responsive behavior configuration
     */
    public function getResponsiveBehavior(): array
    {
        return $this->responsive_behavior ?? [
            'mobile' => ['hidden' => false, 'collapsed' => false],
            'tablet' => ['hidden' => false, 'collapsed' => false],
            'desktop' => ['hidden' => false, 'collapsed' => false],
        ];
    }

    /**
     * Check if widget should be hidden on mobile
     */
    public function isHiddenOnMobile(): bool
    {
        return $this->getResponsiveBehavior()['mobile']['hidden'] ?? false;
    }

    /**
     * Check if widget should be collapsed by default on mobile
     */
    public function isCollapsedOnMobile(): bool
    {
        return $this->getResponsiveBehavior()['mobile']['collapsed'] ?? $this->is_collapsed;
    }

    /**
     * Scope to get visible instances
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)
                    ->whereHas('widget', function ($q) {
                        $q->where('is_enabled', true);
                    });
    }

    /**
     * Scope to get instances for a specific pane
     */
    public function scopeInPane($query, string $pane)
    {
        return $query->where('pane', $pane);
    }

    /**
     * Scope to get instances ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Get available panes
     */
    public static function getPanes(): array
    {
        return [
            'left' => 'Left Sidebar',
            'center' => 'Main Content',
            'right' => 'Right Sidebar',
        ];
    }

    /**
     * Create default widget instances for a layout
     */
    public static function createDefaultInstances(FeedLayout $layout): void
    {
        $widgets = FeedWidget::enabled()->get()->keyBy('widget_key');

        $defaultInstances = [
            // Left sidebar
            ['widget_key' => 'announcements', 'pane' => 'left', 'position' => 0],
            ['widget_key' => 'events', 'pane' => 'left', 'position' => 1],
            
            // Center feed
            ['widget_key' => 'daily_verse', 'pane' => 'center', 'position' => 0],
            ['widget_key' => 'post_feed', 'pane' => 'center', 'position' => 1],
            
            // Right sidebar
            ['widget_key' => 'prayer_requests', 'pane' => 'right', 'position' => 0],
        ];

        foreach ($defaultInstances as $instance) {
            $widget = $widgets->get($instance['widget_key']);
            if (!$widget) continue;

            static::create([
                'layout_id' => $layout->id,
                'widget_id' => $widget->id,
                'pane' => $instance['pane'],
                'position' => $instance['position'],
                'is_visible' => true,
                'is_collapsible' => true,
            ]);
        }
    }
}