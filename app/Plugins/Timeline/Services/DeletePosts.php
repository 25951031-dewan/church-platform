<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class DeletePosts
{
    public function execute(array $postIds): void
    {
        $posts = Post::whereIn('id', $postIds)->get();

        foreach ($posts as $post) {
            $post->media()->delete();
            $post->delete();
        }
    }
}
