<?php

namespace kareemsliet\Chat;

use Illuminate\Support\Facades\DB;
use kareemsliet\Chat\Events\ConversationCreated;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Participant;

class ConversationFactory
{
    public static function createGroup(Participant $participant, $attributes = []): Conversation
    {
        $attributes = array_merge($attributes, ["type" => ConversationTypesEnum::Group]);

        $participants = array_fill_keys([$participant->getKey()],["role"=>"admin"]);

        $conversation = static::create($participants, $attributes);

        if (\kareemsliet\Chat\Helper::eventsEnabled()) {
            event(new ConversationCreated($conversation));
        }

        return $conversation;
    }

    public static function createPrivate(Participant $participant, Participant $otherParticipant, array $attributes = []): Conversation
    {
        $attributes = array_merge($attributes, ["type" => ConversationTypesEnum::Private]);

        $participants = [$participant->getKey()];

        if ($participant->isNot($otherParticipant)) {
            $participants[] = $otherParticipant->getKey();
        }

        $conversation = static::create($participants, $attributes);

        if (\kareemsliet\Chat\Helper::eventsEnabled()) {
            event(new ConversationCreated($conversation));
        }

        return $conversation;
    }

    public static function findOrCreatePrivate(Participant $participant, Participant $otherParticipant, array $attributes = [])
    {
        $conversation = static::FindPrivateWithOtherParticipant($participant, $otherParticipant);

        if (!$conversation) {
            $conversation = static::createPrivate($participant, $otherParticipant, $attributes);
        }

        return $conversation;
    }

    public static function FindPrivateWithOtherParticipant(Participant $participant, Participant $otherParticipant): Conversation|null
    {
        $builder = $participant->conversationsBuilder()->where("type",'=',ConversationTypesEnum::Private);

        if($participant->is($otherParticipant)) {
            $builder->has('participants', '=', 1);
        }

        if($participant->isNot($otherParticipant)) {
            $builder->whereParticipant($otherParticipant);
        }

        return $builder->first();
    }

    public static function create(array $participants, array $attributes = []): Conversation
    {
        $conversation = DB::transaction(function () use ($attributes,$participants) {

            $conversation = Conversation::create($attributes);

            $conversation->participants()->attach($participants);

            return $conversation;
        
        });

        return $conversation;
    }
}


