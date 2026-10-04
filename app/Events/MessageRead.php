<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $readerId,
        public array $messageIds
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel(
                'chat.'.$this->conversationId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }
}
