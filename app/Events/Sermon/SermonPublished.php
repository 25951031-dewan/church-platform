<?php

namespace App\Events\Sermon;

use App\Events\BaseEvent;
use App\Plugins\Sermons\Models\Sermon;

class SermonPublished extends BaseEvent
{
    /**
     * The sermon instance.
     */
    public Sermon $sermon;

    /**
     * Create a new event instance.
     */
    public function __construct(Sermon $sermon)
    {
        parent::__construct($sermon->user_id ?? null, $sermon->church_id);
        $this->sermon = $sermon;
    }
}
