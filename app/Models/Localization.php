<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Localization extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'lines' => 'array',
    ];
}
