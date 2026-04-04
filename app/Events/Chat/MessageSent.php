<?php

namespace App\Events\Chat;

use App\Events\BaseEvent;
use Common\Chat\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent extends BaseEvent implements ShouldBroadcast
{
    /**
     * The message instance.
     */
    public Message $message;

    /**
     * The conversation ID.
     */
    public int $conversationId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        parent::__construct($message->user_id);
        $this->message = $message;
        $this->conversationId = $message->conversation_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
