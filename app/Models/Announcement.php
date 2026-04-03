<?php
namespace App\Models;
use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToChurch;

    protected $fillable = [
        'church_id', 'title', 'content', 'type', 'link', 'is_active',
        'start_date', 'end_date', 'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }
}
