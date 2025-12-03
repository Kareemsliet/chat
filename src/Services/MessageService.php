<?php

namespace kareemsliet\Chat\Services;

use kareemsliet\Chat\BaseMessage;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;
use kareemsliet\Chat\SendMessageCommand;

class MessageService extends BaseMessage
{
    /**
     * Create a new instance of MessageService.
     *
     * @param Participant $participant
     * @param Message $message
     */
    public static function set(Participant $participant,Message $message)
    {
        return new static($participant,$message);
    }

    /**
     * Send a message to conversation.
     *
     * @param Conversation $conversation
     * @param Participant $sender
     * @param string $content
     * @param array $attachments
     * @param Message|string|null $reply
     * @return static
     */
    public static function send(
        Conversation $conversation,
        Participant $sender,
        string $content,
        array $attachments = [],
        Message|string|null $reply = null,
    ): static {

        $messageCommand = new SendMessageCommand(
            $conversation,
            $sender,
            $content,
            $attachments,
            $reply
        );

        $message = $messageCommand->handle();

        return new static($sender, $message);
    
    }
}