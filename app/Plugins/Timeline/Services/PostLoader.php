<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class PostLoader
{
    public function load(Post $post): Post
    {
        return $post->load([
            'user:id,name,avatar',
            'media',
            'reactions',
        ])->loadCount(['comments', 'reactions']);
    }

    public function loadForFeed(Post $post): array
    {
        $this->load($post);

        $data = $post->toArray();
        $data['reaction_counts'] = $post->reactionCounts();
        $data['current_user_reaction'] = $post->currentUserReaction()?->type;

        return $data;
    }
}
