<?php

namespace kareemsliet\Chat\Traits;

use kareemsliet\Chat\Models\Participant;

trait HasParticipant
{
    /**
     * Get the participant morph relation.
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function participant()
    {
        return $this->morphOne(Participant::class, 'participantable');
    }

    /**
     * Check if the model has a participant.
     * @return bool
     */
    public function hasParticipant(): bool
    {
        return $this->participant()->exists();
    }

    /**
     * Get the participant instance.
     * @return Participant|null
     */
    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    /**
     * Create a new participant for the model.
     * @return Participant
     */
    public function createParticipant(): Participant
    {
        return $this->participant()->create();
    }

    /**
     * Find or create a participant for the model.
     * @return Participant
     */
    public function findOrCreateParticipant(): Participant
    {
        $participant = $this->getParticipant();

        if(is_null($participant))
        {
            $participant = $this->createParticipant();
        }

        return $participant;
    }
}