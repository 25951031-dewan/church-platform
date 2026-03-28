<?php
// common/foundation/src/Reactions/Models/Reaction.php

namespace Common\Reactions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reaction extends Model
{
    protected $guarded = ['id'];

    public const TYPES = ['like', 'pray', 'amen', 'love', 'celebrate'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
