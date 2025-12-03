<?php

namespace kareemsliet\Chat\Events;

use Illuminate\Broadcasting\InteractsWithBroadcasting;
use kareemsliet\Chat\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class MessageWasEdited implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets,InteractsWithBroadcasting;

    protected Message $message;

    public $connection;

    public function __construct(Message $message)
    {
        $this->message = $message;
        
        $this->connection = \kareemsliet\Chat\Helper::broadcastQueueConnection();

        $this->broadcastVia(\kareemsliet\Chat\Helper::broadcastConnection());
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.conversations.{$this->message->conversation_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            "message" => [
                "id" => $this->message->getKey(),
                "content" => $this->message->content,
                "conversation" => [
                    "id" => $this->message->conversation_id,
                ],
                "sender" => [
                    "id" => $this->message->sender->participantable_id,
                    "type" => $this->message->sender->participantable_type,
                ],
                "createdAt" => $this->message->created_at,
                "lastUpdatedAt" => $this->message->updated_at,
            ],
        ];
    }
}
