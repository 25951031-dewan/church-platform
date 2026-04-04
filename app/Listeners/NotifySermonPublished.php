<?php

namespace App\Listeners;

use App\Events\Sermon\SermonPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifySermonPublished implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SermonPublished $event): void
    {
        $sermon = $event->sermon;

        \Log::info('New sermon published', [
            'sermon_id' => $sermon->id,
            'title' => $sermon->title,
            'church_id' => $event->churchId,
        ]);

        // TODO: Send push notifications to church members
        // TODO: Send email digest to subscribed users
    }
}
