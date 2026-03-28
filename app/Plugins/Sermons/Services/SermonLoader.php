<?php

namespace App\Plugins\Sermons\Services;

use App\Plugins\Sermons\Models\Sermon;

class SermonLoader
{
    public function load(Sermon $sermon): Sermon
    {
        return $sermon->load([
            'author:id,name,avatar',
            'sermonSeries:id,name,slug',
            'speakerProfile:id,name,slug,image',
            'reactions',
        ])->loadCount(['comments', 'reactions']);
    }

    public function loadForDetail(Sermon $sermon): array
    {
        $this->load($sermon);

        $data = $sermon->toArray();
        $data['reaction_counts'] = $sermon->reactionCounts();

        $userId = auth()->id();
        if ($userId) {
            $data['current_user_reaction'] = $sermon->currentUserReaction()?->type;
        }

        return $data;
    }
}
