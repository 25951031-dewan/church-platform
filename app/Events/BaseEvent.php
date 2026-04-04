<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who triggered the event.
     */
    public ?int $userId;

    /**
     * The church context.
     */
    public ?int $churchId;

    /**
     * Create a new event instance.
     */
    public function __construct(?int $userId = null, ?int $churchId = null)
    {
        $this->userId = $userId ?? auth()->id();
        $this->churchId = $churchId;
    }
}
