<?php

namespace App\Events\Timeline;

use App\Events\BaseEvent;
use App\Plugins\Timeline\Models\Post;

class PostCreated extends BaseEvent
{
    /**
     * The post instance.
     */
    public Post $post;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post)
    {
        parent::__construct($post->user_id, $post->church_id);
        $this->post = $post;
    }
}
