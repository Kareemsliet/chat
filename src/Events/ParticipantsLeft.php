<?php

namespace kareemsliet\Chat\Events;

use Illuminate\Foundation\Events\Dispatchable;
use kareemsliet\Chat\Models\Conversation;

class ParticipantsLeft
{
    use Dispatchable;
    
    /**
     * Create a new event instance.
     *
     * @param Conversation $conversation
     * @param array $participants
     * @return void
     */
    public function __construct(public Conversation $conversation, public array $participants){}
}
