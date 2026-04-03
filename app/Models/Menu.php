<?php
namespace App\Models;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'name', 'location', 'items', 'is_active'];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByLocation($query, $location) { return $query->where('location', $location); }
}
