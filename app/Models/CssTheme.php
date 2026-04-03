<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CssTheme extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'is_dark' => 'boolean',
        'is_default' => 'boolean',
        'colors' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function toCssVariables(): string
    {
        $colors = $this->colors ?? [];
        $vars = [];
        foreach ($colors as $key => $value) {
            $vars[] = "--{$key}: {$value};";
        }
        return ':root { ' . implode(' ', $vars) . ' }';
    }
}
