<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CssTheme extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'is_dark',
        'default_dark',
        'default_light',
        'values',
        'font',
    ];

    protected $casts = [
        'is_dark' => 'boolean',
        'default_dark' => 'boolean',
        'default_light' => 'boolean',
        'values' => 'array',
        'font' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Convert theme values to CSS custom properties string.
     */
    public function toCssVariables(): string
    {
        $values = $this->values ?? [];
        $vars = [];
        
        foreach ($values as $key => $value) {
            $vars[] = "--{$key}: {$value};";
        }
        
        return ':root { ' . implode(' ', $vars) . ' }';
    }

    /**
     * Get the default theme for a mode (dark/light).
     */
    public static function getDefault(bool $isDark = true): ?self
    {
        return self::where($isDark ? 'default_dark' : 'default_light', true)->first();
    }
}
