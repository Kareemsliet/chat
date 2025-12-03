<?php

namespace kareemsliet\Chat;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use kareemsliet\Chat\Events\MessageWasSent;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;

class SendMessageCommand
{
    public Conversation $conversation;
    public Participant $sender;
    public string $content;
    protected Message|string|null $reply;
    public array $attachments;

    public function __construct($conversation, $sender, $content , $attachments = [], $reply = null)
    {
        $this->conversation = $conversation;
        
        $this->sender = $sender;
        
        $this->content = $content;
        
        $this->attachments = $attachments;

        $this->reply = $reply;
    }

    public function handle(): Message
    {
        if(empty(trim($this->content)) && empty($this->attachments))
        {
            throw new \kareemsliet\Chat\Exceptions\MessageException("Message content or attachments are required to send a message");
        }

        $message = $this->create();

        if(\kareemsliet\Chat\Helper::broadcastEnabled())
        {
           MessageWasSent::broadcast($message)->toOthers();
        }

        return $message;
    }

    public function create(): Message
    {
        $attributes = $this->getAttributes();

        $participants = $this->getParticipants();

        return DB::transaction(function () use ($attributes, $participants) {
            $message = Message::create($attributes);
            $message->participants()->attach($participants);
            return $message;
        });
    }

    protected function getParticipants(): array
    {
        $participants = $this->conversation->participantsBuilder()->active()->pluck('id');

        return $participants->mapWithKeys(function ($participant) {
            return [
                $participant => [
                    'is_sender' => $participant === $this->sender->id ? 1 : 0,
                ],
            ];
        })->all();
    }

    protected function getAttributes(): array
    {
        $attributes = [
            'content' => $this->content,
            'participant_id' => $this->sender->id,
            'conversation_id' => $this->conversation->id,
        ];

        if (!is_null($this->reply)) {
            $attributes['reply_message_id'] = $this->reply instanceof Message ? $this->reply->id : $this->reply;
        }

        if (!empty($this->attachments)) {
            $attributes["attachments"] = Arr::isAssoc($this->attachments) ? [$this->attachments] : $this->attachments;
        }

        return $attributes;
    }
}