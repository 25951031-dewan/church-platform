<?php

namespace App\Plugins\Timeline\Services;

use App\Plugins\Timeline\Models\Post;

class CrupdatePost
{
    public function execute(array $data, ?Post $post = null): Post
    {
        if ($post) {
            $post->update([
                'content' => $data['content'] ?? $post->content,
                'type' => $data['type'] ?? $post->type,
                'visibility' => $data['visibility'] ?? $post->visibility,
                'scheduled_at' => $data['scheduled_at'] ?? $post->scheduled_at,
            ]);
        } else {
            $post = Post::create([
                'user_id' => $data['user_id'],
                'church_id' => $data['church_id'] ?? null,
                'group_id' => $data['group_id'] ?? null,
                'type' => $data['type'] ?? 'text',
                'content' => $data['content'],
                'visibility' => $data['visibility'] ?? 'public',
                'is_pinned' => $data['is_pinned'] ?? false,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'published_at' => isset($data['scheduled_at']) ? null : now(),
            ]);
        }

        return $post;
    }
}
