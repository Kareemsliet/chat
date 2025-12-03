<?php

namespace kareemsliet\Chat;

class Helper
{
    /**
     * Check if UUID primary keys are enabled for conversations.
     */
    public static function useUUIDForConversations(): bool
    {
        return config('chat.use_uuid_conversations',false);
    }

    /**
     * Check if UUID primary keys are enabled for messages.
     */
    public static function useUUIDForMessages(): bool
    {
        return config('chat.use_uuid_messages',false);
    }

    /**
     * Check if events are enabled for chat actions.
     */
    public static function eventsEnabled(): bool
    {
        return config('chat.events_enabled',false);
    }

    /**
     * Check if broadcasting is enabled for chat events.
     */
    public static function broadcastEnabled(): bool
    {
        return config('chat.broadcasting.enabled',true);
    }

    /**
     * Get the broadcast connection for broadcasting.
     */
    public static function broadcastConnection(): string
    {
        return config('chat.broadcasting.connection','null');
    }

    /**
     * Get the queue connection for broadcasting.
     */
    public static function broadcastQueueConnection(): string
    {
        return config('chat.broadcasting.queue_connection','sync');
    }
}