<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class DeletePosts
{
    public function execute(array $postIds): void
    {
        $posts = Post::whereIn('id', $postIds)->get();

        foreach ($posts as $post) {
            $post->reactions()->delete();
            $post->comments()->delete();
            $post->media()->delete();
            $post->delete();
        }
    }
}
