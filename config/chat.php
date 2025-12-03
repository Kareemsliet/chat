<?php

return [

    /*
    |--------------------------------------------------------------------------
    | UUID Primary Keys - Conversations
    |--------------------------------------------------------------------------
    |
    | Enable UUID primary keys for conversations table.
    | Use UUIDs instead of auto-incrementing integers for primary keys.
    |
    */
    'use_uuid_conversations' => false,

    /*
    |--------------------------------------------------------------------------
    | UUID Primary Keys - Messages
    |--------------------------------------------------------------------------
    |
    | Enable UUID primary keys for messages table.
    | Use UUIDs instead of auto-incrementing integers for primary keys.
    |
    */
    'use_uuid_messages' => false,

    /*
    |--------------------------------------------------------------------------
    | Conversation Events Settings
    |--------------------------------------------------------------------------
    |
    | Events will fire for chat actions like messages sent, edited, etc.
    | \kareemsliet\Chat\Events\ConversationCleared
    | \kareemsliet\Chat\Events\ConversationUpdated
    | \kareemsliet\Chat\Events\ConversationCreated
    | \kareemsliet\Chat\Events\ConversationLeft
    | \kareemsliet\Chat\Events\ParticipantsJoined
    | \kareemsliet\Chat\Events\ParticipantsLeft
    | \kareemsliet\Chat\Events\ParticipantsRoleChanged
    |
    | To enable, set to true, and then create listeners to watch these events.
    |
    */
    'events_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Settings
    |--------------------------------------------------------------------------
    |
    | Events will fire for chat actions like messages sent, edited, etc.
    | \kareemsliet\Chat\Events\MessageWasSent
    | \kareemsliet\Chat\Events\MessageWasEdited
    |
    | Configure real-time broadcasting for messages events.
    | 
    | To enable broadcasting, set 'enabled' to true.
    |
    */
    'broadcasting' => [
        /*
         * Enable or disable real-time broadcasting for messages events.
         */
        'enabled' => true,

        /*
         * The broadcast connection to use.
         */
        "connection" => env('BROADCAST_CONNECTION'),

        /*
         * Queue broadcast events instead of dispatching them immediately.
         */
        'queue_connection' => env('QUEUE_CONNECTION'),
    ],
];