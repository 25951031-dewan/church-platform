<?php
// common/foundation/src/Reactions/Traits/HasReactions.php

namespace Common\Reactions\Traits;

use Common\Reactions\Models\Reaction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasReactions
{
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function reactionCounts(): array
    {
        return $this->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    public function currentUserReaction(): ?Reaction
    {
        if (!auth()->check()) return null;
        return $this->reactions()->where('user_id', auth()->id())->first();
    }
}
