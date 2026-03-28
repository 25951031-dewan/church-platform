<?php

namespace App\Plugins\Timeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    protected $guarded = ['id'];

    protected $table = 'post_media';

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
