<?php

namespace kareemsliet\Chat\Contracts;

use DateTime;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use Illuminate\Database\Eloquent\Collection as ElquentCollection;
use kareemsliet\Chat\Models\Participant;

interface MessageContract
{
    /**
     * Get all the participants of the message.
     * @param callable|null $query
     * @return ElquentCollection
     */
    public function participants(callable|null $query = null): ElquentCollection;

    /**
     * Get all the participants who have read the message.
     * @return ElquentCollection
     */
    public function readBy(): ElquentCollection;

    /**
     * Get the sender of the message.
     * @return Participant
     */
    public function getSender(): Participant;

    /**
     * Get the message.
     *
     * @return Message
     */
    public function getMessage(): Message;

    /**
     * Get the conversation of the message.
     *
     * @return Conversation
     */
    public function getConversation(): Conversation;

    /**
     * Get the reply message of the message.
     *
     * @return Message|null
     */
    public function getReplyMessage(): Message|null;

    /**
     * Check if the message is sent by a current participant.
     *
     * @return bool
     */
    public function isSender(): bool;

    /**
     * Check if the message is starred for a participant.
     *
     * @return bool
     */
    public function isStarred(): bool;

    /**
     * Get the starred at timestamp for a participant.
     *
     * @return DateTime|null
     */
    public function starredAt(): ?DateTime;

    /**
     * Get the created at timestamp of the message.
     *
     * @return DateTime
     */
    public function createdAt(): DateTime;

    /**
     * Get the last updated at timestamp of the message.
     *
     * @return DateTime
     */
    public function updatedAt(): DateTime;

    /**
     * Get the delivered at timestamp of the message.
     *
     * @return DateTime|null
     */
    public function deliveredAt(): ?DateTime;

    /**
     * Mark the message as read for a participant (if not the sender).
     */
    public function markAsRead();

    /**
     * Star the message for a participant.
     *
     * @return bool
     */
    public function star(): bool;

    /**
     * Unstar the message for a participant.
     *
     * @return bool
     */
    public function unstar(): bool;

    /**
     * Toggle the star status of the message for a participant.
     *
     * @return bool
     */
    public function toggleStar(): bool;

    /**
     * Check if the message has a reply.
     * @return bool
     */
    public function hasReply(): bool;

    /**
     * Get the reply message instance.
     * @return static
     */
    public function replyMessage(): static;

    /**
     * Get the age of the message in the given unit.
     * 
     * this method returns the age of the message in the specified time unit.
     * 
     * This allows units like "year","month","week","day","hour","minute","second".
     *
     * @param string $unit
     * @return float
     */
    public function getAgeIn(string $unit = "day"): float;

    /**
     * delete the message for a participant.
     * @return bool
     */
    public function deleteForMe(): bool;

    /**
     * Delete the message for a participant in a specific unit with consideration for max age.
     * 
     * @param string $unit
     * @param int|float $maxAge
     * @return bool
     */
    public function deleteForMeIn(string $unit = "day", int|float $maxAge = 1):bool;

    /**
     * Delete the message for all participants and delete it from the database.
     * @return ?bool
     */
    public function delete(): ?bool;

    /**
     * Delete the message for all participants in a specific unit with consideration for max age.
     * @param string $unit
     * @param int|float $maxAge
     * @return ?bool
     */
    public function deleteIn(string $unit = "day", int|float $maxAge = 1): ?bool;

    /**
     * Edit the content of the message.
     * @param string $newContent
     * @return bool
     */
    public function edit(string $newContent):bool;

    /**
     * Edit the content of the message in a specific unit with consideration for max age.
     * @param string $newContent
     * @param string $unit
     * @param int|float $maxAge
     * @return bool
     */
    public function editIn(string $newContent, string $unit = "day", int|float $maxAge = 1):bool;

    /**
     * Clone the message instance.
     * @return static
     */
    public function clone(): static;

    /**
     * Get the participants builder for the message.
     * 
     * @param callable|null $query
     * @return \kareemsliet\Chat\Builders\MessageBuilder
     */
    public function participantsBuilder(?callable $query = null): \kareemsliet\Chat\Builders\MessageBuilder;
}
