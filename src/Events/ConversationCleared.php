<?php

namespace kareemsliet\Chat\Events;

use Illuminate\Foundation\Events\Dispatchable;
use kareemsliet\Chat\Models\Conversation;

class ConversationCleared
{
    use Dispatchable;
    public function __construct(public Conversation $conversation){}
}
