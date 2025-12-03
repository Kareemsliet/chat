<?php

namespace kareemsliet\Chat\Events;

use Illuminate\Foundation\Events\Dispatchable;
use kareemsliet\Chat\Models\Conversation;

class ConversationLeft
{
    use Dispatchable;
    public function __construct(public Conversation $conversation){}
}
