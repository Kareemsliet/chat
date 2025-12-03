<?php

namespace kareemsliet\Chat\Events;

use Illuminate\Foundation\Events\Dispatchable;
use kareemsliet\Chat\Models\Conversation;

class ParticipantsRoleChanged
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param Conversation $conversation
     * @param array $participants
     * @param string $role
     * @return void
     */
    public function __construct(public Conversation $conversation,public array $participants,public string $role) {}
}
