<?php

namespace App\Listeners;

use App\Events\Timeline\PostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyPostMentions implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PostCreated $event): void
    {
        $post = $event->post;

        // Extract @mentions from post content
        preg_match_all('/@(\w+)/', $post->content ?? '', $matches);
        
        if (empty($matches[1])) {
            return;
        }

        \Log::info('Post created with mentions', [
            'post_id' => $post->id,
            'mentions' => $matches[1],
        ]);

        // TODO: Notify mentioned users
    }
}
