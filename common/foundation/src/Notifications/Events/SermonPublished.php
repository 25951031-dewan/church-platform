<?php

namespace Common\Notifications\Events;

use App\Plugins\Sermons\Models\Sermon;

class SermonPublished
{
    public function __construct(public Sermon $sermon) {}
}
