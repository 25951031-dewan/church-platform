<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChurchSetting extends Model
{
    protected $fillable = ['church_id', 'key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public function church()
    {
        return $this->belongsTo(Church::class);
    }
}
