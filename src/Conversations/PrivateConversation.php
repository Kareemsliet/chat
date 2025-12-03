<?php

namespace kareemsliet\Chat\Conversations;

use kareemsliet\Chat\BaseConversation;
use kareemsliet\Chat\ConversationFactory;
use kareemsliet\Chat\ConversationTypesEnum;
use kareemsliet\Chat\Exceptions\ConversationException;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Participant;

class PrivateConversation extends BaseConversation
{
    public function __construct(Participant $participant, Conversation $conversation)
    {
        if ($conversation->type !== ConversationTypesEnum::Private) {
            throw new ConversationException("The provided conversation is not a private conversation.");
        }
        parent::__construct($participant, $conversation);
    }

    /**
     * Create a new private conversation between two participants.
     * @param Participant $participant
     * @param Participant $otherParticipant
     * @param array $attributes
     * @return PrivateConversation
     */
    public static function create($participant, $otherParticipant, $attributes = []): static
    {   
        $conversation = ConversationFactory::createPrivate($participant, $otherParticipant,$attributes);
        
        return new static(
            $participant,
            $conversation,
        );
    }

    /**
     * Set an existing conversation with a participant or create a new one if it doesn't exist.
     * 
     * @param Participant $participant
     * @param Participant $otherParticipant
     * @param array $attributes
     * @return PrivateConversation
     */
    public static function setOrCreate($participant, $otherParticipant, $attributes = []): static
    {
        $conversation = ConversationFactory::findOrCreatePrivate($participant, $otherParticipant, $attributes);
        
        return new static(
            $participant,
            $conversation
        );
    }
}