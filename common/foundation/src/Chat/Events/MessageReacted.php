<?php

namespace Common\Chat\Events;

use Common\Auth\Models\User;
use Common\Chat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public string $emoji,
        public User $user,
        public string $action // 'added' or 'removed'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.reacted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'emoji' => $this->emoji,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'action' => $this->action,
            'reactions' => $this->message->fresh()->getReactionCounts(),
        ];
    }
}
