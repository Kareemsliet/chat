<?php

namespace kareemsliet\Chat\Facades;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;
use kareemsliet\Chat\ChatManager;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Conversations\GroupConversation;
use kareemsliet\chat\Conversations\PrivateConversation;
use kareemsliet\Chat\Models\Participant;

/**
 * 
 * @method static ChatManager for(Participant $participant) Create a chat manager for a specific participant
 * @method static ChatManager forUser(Model $user) Create a chat manager for a user model
 * @method static ChatManager forAuth(?string $guard = null) Create a chat manager for the authenticated user
 * @method static Participant participant() Get the current participant
 * 
 * @method static GroupConversation newGroup(array $attributes = []) Create a new group conversation
 * @method static PrivateConversation newPrivate(Model $user, array $attributes = []) Create a new private conversation with the given user
 * @method static PrivateConversation privateWith(Model $user, array $attributes = []) Find or create a private conversation with the given user
 * 
 * @method static GroupConversation|PrivateConversation findById(string $id) Find a conversation by ID and return the appropriate handler
 * @method static GroupConversation|PrivateConversation find(Conversation $conversation) Find a conversation and return the appropriate handler
 * @method static PrivateConversation privateById(string $id) Get a private conversation by ID
 * @method static PrivateConversation private(Conversation $conversation) Get a private conversation handler
 * @method static GroupConversation groupById(string $id) Get a group conversation by ID
 * @method static GroupConversation group(Conversation $conversation) Get a group conversation handler
 * 
 * @method static \Illuminate\Database\Eloquent\Collection all(?callable $query = null, bool $useDefaultSorting = true) Get all conversations for the current participant
 * @method static \Illuminate\Database\Eloquent\Collection withParticipant(Model $user, ?callable $query = null, bool $useDefaultSorting = true) Get conversations with a specific user
 * @method static \Illuminate\Database\Eloquent\Collection unread(?callable $query = null, bool $useDefaultSorting = true) Get unread conversations
 * @method static \Illuminate\Database\Eloquent\Collection favorited(?callable $query = null, bool $useDefaultSorting = true) Get favorited conversations
 * @method static \Illuminate\Database\Eloquent\Collection pinned(?callable $query = null, bool $useDefaultSorting = true) Get pinned conversations
 * 
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator paginate(int $perPage = 15, ?callable $query = null, bool $useDefaultSorting = true, array $options = []) Paginate the conversations
 * @method static \kareemsliet\Chat\Builders\ConversationBuilder query(?callable $callback = null) Get a conversation query builder for the current participant
 * 
 * @method static \kareemsliet\Chat\Services\MessageService message(Message $message) Get message service for a specific message
 * @method static \kareemsliet\Chat\Services\MessageService messageById(string $id) Get message by ID for the current participant
 * @method static \Illuminate\Database\Eloquent\Collection messages(?callable $callback = null) Get all messages for the current participant
 * @method static \Illuminate\Database\Eloquent\Collection starredMessages(?callable $callback = null) Get all starred messages for the current participant
 * @method static \kareemsliet\Chat\Builders\MessageBuilder messagesQuery(?callable $callback = null) Get messages query builder for current participant
 * 
 * @see \kareemsliet\Chat\ChatManager
 */
class Chat extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'chat';
    }
}