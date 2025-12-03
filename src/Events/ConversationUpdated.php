<?php

namespace kareemsliet\Chat\Events;

use kareemsliet\Chat\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;

class ConversationUpdated
{
    use Dispatchable;
    public function __construct(public Conversation $conversation, public array $columns = []){}
}
