<?php

namespace kareemsliet\Chat;

use Illuminate\Database\Eloquent\Model;
use kareemsliet\Chat\Traits\HasParticipant;

class ConversationChannel
{
    /**
     * Authenticate the user's access to the channel.
     */
    public function join(Model $user,string $id): array|bool
    {
        // Check if the user model uses the HasParticipant trait
        // if not, return false
        if (!in_array(HasParticipant::class, class_uses_recursive($user))) {
            return false;
        }

        // Get the participant instance for the user Auth
        // for this, we use getParticipant method from HasParticipant trait
        $participant = $user->getParticipant();

        // Check if the user is a participant
        // if not, return false
        if(is_null($participant))
        {
            return false;
        }

        // Check if the participant is part of the current conversation on event fire
        return $participant->conversationsBuilder()->where('id', $id)->exists();
    }
}
