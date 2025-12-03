<?php

namespace kareemsliet\Chat\Models;

use kareemsliet\Chat\Builders\ConversationBuilder;
use Illuminate\Database\Eloquent\Model;
use kareemsliet\Chat\Builders\MessageBuilder;

class Participant extends Model
{
    protected $table = "participants";

    protected $fillable = [
        "participantable_id",
        "participantable_type",
    ];

    public function participantable()
    {
        return $this->morphTo("participantable");
    }

    public function conversationsBuilder()
    {
        return ConversationBuilder::make($this->conversations());
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, "participant_has_conversation", "participant_id", "conversation_id")->withTimestamps();
    }

    public function messages()
    {
        return $this->belongsToMany(Message::class, "participant_has_message", "participant_id", "message_id")->withTimestamps();
    }

    public function messagesBuilder()
    {
        return MessageBuilder::make($this->messages());
    }
}