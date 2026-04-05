<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToChurch;

class FeedLayout extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id',
        'name',
        'is_active',
        'layout_data',
        'left_sidebar_config',
        'right_sidebar_config',
        'mobile_config',
        'responsive_settings',
        'sort_order',
    ];

    protected $casts = [
        'layout_data' => 'json',
        'left_sidebar_config' => 'json',
        'right_sidebar_config' => 'json',
        'mobile_config' => 'json',
        'responsive_settings' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get the widget instances for this layout
     */
    public function widgetInstances(): HasMany
    {
        return $this->hasMany(FeedWidgetInstance::class, 'layout_id')
                    ->orderBy('pane')
                    ->orderBy('position');
    }

    /**
     * Get widget instances for a specific pane
     */
    public function getWidgetsForPane(string $pane): HasMany
    {
        return $this->widgetInstances()->where('pane', $pane)->where('is_visible', true);
    }

    /**
     * Get the layout configuration for a specific pane
     */
    public function getPaneConfig(string $pane): array
    {
        return match ($pane) {
            'left' => $this->left_sidebar_config ?? [],
            'right' => $this->right_sidebar_config ?? [],
            'center' => $this->layout_data['center'] ?? [],
            default => [],
        };
    }

    /**
     * Check if this is the active layout for the church
     */
    public function getIsActiveLayoutAttribute(): bool
    {
        return $this->is_active && 
               static::where('church_id', $this->church_id)
                     ->where('is_active', true)
                     ->where('id', $this->id)
                     ->exists();
    }

    /**
     * Scope to get only active layouts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get layouts for a specific church
     */
    public function scopeForChurch($query, $churchId = null)
    {
        if ($churchId === null) {
            return $query; // Return all layouts if no church ID
        }
        return $query->where('church_id', $churchId);
    }

    /**
     * Get the default layout data structure
     */
    public static function getDefaultLayoutData(): array
    {
        return [
            'center' => [
                'width' => '100%',
                'max_width' => '600px',
                'spacing' => 'md',
            ],
            'grid' => [
                'columns' => 12,
                'gap' => 16,
            ],
            'breakpoints' => [
                'mobile' => 768,
                'tablet' => 1024,
                'desktop' => 1280,
            ],
        ];
    }
}