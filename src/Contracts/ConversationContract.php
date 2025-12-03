<?php

namespace kareemsliet\Chat\Contracts;

use kareemsliet\Chat\Models\Message;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use kareemsliet\Chat\Services\MessageService;

interface ConversationContract
{
    /**
     * Get the underlying conversation model from the conversation property.
     *
     * @return \kareemsliet\Chat\Models\Conversation
     */
    public function getConversation(): \kareemsliet\Chat\Models\Conversation;

    /**
     * Get the creation date of the conversation.
     *
     * @return \DateTime
     */
    public function createdAt(): \DateTime;

    /**
     * Get all participants of the conversation.
     *
     * @param callable|null $query
     * @return EloquentCollection
     */
    public function participants(callable|null $query = null): EloquentCollection;

    /**
     * Update the conversation with the given attributes.
     * @param array $attributes
     * @return bool
     */
    public function update(array $attributes): bool;

     /**
     * Get the date when the participant joined the conversation.
     * @return \DateTime|null
     */
    public function joinedAt(): \DateTime|null;

    /**
     * Get conversation type.
     *
     * @return string
     */
    public function type(): string;

    /**
     * Check if the conversation is a group conversation.
     *
     * @return bool
     */
    public function isGroup(): bool;

    /**
     * Check if the conversation is pinned for the current participant.
     *
     * @return bool
     */
    public function isPinned(): bool;

    /**
     * Pin the conversation for current participant.
     *
     * @return bool
     */
    public function pin(): bool;

    /**
     * Unpin the conversation for current participant.
     *
     * @return bool
     */
    public function unpin(): bool;

    /**
     * Toggle the pin status of the conversation for current participant.
     *
     * @return bool
     */
    public function togglePin(): bool;

    /**
     * Check if the conversation is favorited by the current participant.
     *
     * @return bool
     */
    public function isFavorited(): bool;

    /**
     * Favorite the conversation for current participant.
     *
     * @return bool
     */
    public function favorite(): bool;

    /**
     * Unfavorite the conversation for current participant.
     *
     * @return bool
     */
    public function unfavorite(): bool;

    /**
     * Toggle the favorite status of the conversation for current participant.
     *
     * @return bool
     */
    public function toggleFavorite(): bool;

    /**
     * Delete the conversation for current participant.
     *
     * must delete conversation messages for current participant before delete.
     * 
     * @return bool
     * @see clear()
     */
    public function deleteForMe(): bool;

    /**
     * Delete the conversation entirely.
     *
     * delete the messages and participants associated with the conversation.
     * 
     * @return bool
     */
    public function delete(): bool;

    /**
     * Get message by ID at the current conversation.
     * @param string $id
     * @return MessageService
     */
    public function messageById(string $id): MessageService;

    /**
     * Set message at the current conversation.
     * @param Message $message
     * @return MessageService
     */
    public function message(Message $message): MessageService;

    /**
     * Create a new message in the conversation.
     * @param string $content
     * @param array $attachments
     * @param Message|string|null $reply
     * @return MessageService
     */
    public function sendMessage(string $content, array $attachments = [] , Message|string|null $reply = null): MessageService;

    /**
     * Get the last message in the conversation.
     *
     * @return MessageService|null
     */
    public function latestMessage(): MessageService|null;

    /**
     * Mark all unread messages in the conversation as read.
     *
     * @return bool
     */
    public function markAsRead(): bool;

    /**
     * Clear all messages in the conversation for current participant.
     * @return bool
     */
    public function clear(): bool;

    /**
     * Get all messages in the conversation.
     * @param callable|null $query
     * @param int|null $offset
     * @param int|null $limit
     * @return EloquentCollection
     */
    public function messages(?callable $query = null, ?int $offset = null, ?int $limit = null): EloquentCollection;

    /**
     * Get paginated messages in the conversation.
     * 
     * @param int $perPage
     * @param callable|null $query
     * @param array $options // Pagination options like 'page_name', and 'page'
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function messagesPaginated(int $perPage = 15, ?callable $query = null, array $options = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get the count of unread messages in the conversation for current participant.
     * 
     * @return int
     */
    public function unreadCount(): int;

    /**
     * Clone the conversation.
     *
     * @return static
     */
    public function clone(): static;

    /**
     * Get the messages builder for the conversation.
     *
     * @return \kareemsliet\Chat\Builders\MessageBuilder
     */
    public function messagesBuilder(): \kareemsliet\Chat\Builders\MessageBuilder;

    /**
     * Get the participants builder for the conversation.
     *
     * @param callable|null $query
     * @return \kareemsliet\Chat\Builders\ConversationBuilder
     */
    public function participantsBuilder(?callable $query = null): \kareemsliet\Chat\Builders\ConversationBuilder;
}