<?php

namespace App\Models;

use App\Traits\BelongsToChurch;
use Illuminate\Database\Eloquent\Model;

class Verse extends Model
{
    use BelongsToChurch;

    protected $fillable = ['church_id', 'verse_text', 'reference', 'translation', 'display_date', 'is_active', 'created_by'];

    protected $casts = ['display_date' => 'date', 'is_active' => 'boolean'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeToday($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('display_date', today())
                  ->orWhereNull('display_date');
            })
            ->latest();
    }
}
