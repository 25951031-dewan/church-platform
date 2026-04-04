<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class IndexForSearch implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle any searchable model event.
     */
    public function handle(object $event): void
    {
        // Get the model from the event
        $model = $this->getModelFromEvent($event);
        
        if (!$model) {
            return;
        }

        // Check if model is searchable
        if (!method_exists($model, 'searchable')) {
            return;
        }

        // Index the model for search
        $model->searchable();

        \Log::info('Indexed model for search', [
            'model' => get_class($model),
            'id' => $model->id,
        ]);
    }

    /**
     * Extract the model from an event.
     */
    protected function getModelFromEvent(object $event): ?object
    {
        // Try common property names
        $properties = ['post', 'sermon', 'prayerRequest', 'group', 'event', 'book', 'article'];
        
        foreach ($properties as $property) {
            if (property_exists($event, $property)) {
                return $event->$property;
            }
        }

        return null;
    }
}
