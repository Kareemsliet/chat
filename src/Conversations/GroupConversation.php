<?php

namespace kareemsliet\Chat\Conversations;

use Illuminate\Support\Collection as BaseCollection;
use kareemsliet\Chat\BaseConversation;
use kareemsliet\Chat\Concerns\ManagesParticipants;
use kareemsliet\Chat\ConversationFactory;
use kareemsliet\Chat\ConversationTypesEnum;
use kareemsliet\Chat\Events\ConversationLeft;
use kareemsliet\Chat\Events\ParticipantsJoined;
use kareemsliet\Chat\Events\ParticipantsLeft;
use kareemsliet\Chat\Events\ParticipantsRoleChanged;
use kareemsliet\Chat\Exceptions\ConversationException;
use kareemsliet\Chat\Exceptions\UnauthorizedConversationException;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Participant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GroupConversation extends BaseConversation
{
    use ManagesParticipants;

    public function __construct(Participant $participant, Conversation $conversation)
    {
        if ($conversation->type !== ConversationTypesEnum::Group) {
            throw new ConversationException("The provided conversation is not a group conversation.");
        }
        parent::__construct($participant, $conversation);
    }

    /**
     * Create a new group conversation with the current participant.
     * 
     * @param Participant $participant
     * @param array $attributes
     * @return GroupConversation
     */
    public static function create($participant, $attributes = []): static
    {
        return new static($participant, ConversationFactory::createGroup($participant, $attributes));
    }

    /**
     * Authorize the conversation or throw an exception if unauthorized.
     * 
     * throw exception if  callback returns true.
     * 
     * @param callable|null $callback
     * @return static
     */
    public function authorize(callable|null $callback = null):static
    {
        $callback = is_callable($callback) ? $callback($this) : $this->hasLeft();

        throw_if($callback, UnauthorizedConversationException::class);

        return $this;
    }

    /**
     * Get all active participants in the conversation.
     * 
     * @param callable|null $query
     * @return EloquentCollection
     */
    public function activeParticipants($query = null): EloquentCollection
    {
        return $this->participantsBuilder($query)->active()->get();
    }

    /**
     * Get all participants who have left the conversation.
     * 
     * @param callable|null $query
     * @return EloquentCollection
     */
    public function leftParticipants($query = null): EloquentCollection
    {
        return $this->participantsBuilder($query)->inactive()->get();
    }

    /**
     * Get all admin participants in the conversation.
     * 
     * @param callable|null $query
     * @return EloquentCollection
     */
    public function adminParticipants($query = null): EloquentCollection
    {
        return $this->participantsBuilder($query)->admins()->get();
    }

    /**
     * Get the role of the current participant in the conversation.
     * @return string|null
     */
    public function role():string|null
    {
        return $this->getPivotValue("role",null);
    }

    /**
     * Get the date when the participant left the conversation.
     * @return \DateTime|null
     */
    public function leftAt(): \DateTime|null
    {
        return $this->whenPivotValue('left_at', fn($value) => new \DateTime($value));
    }

    /**
     * Check if the current participant is an admin.
     * @return bool
     */
    public function isAdmin(): bool
    {
        return !is_null($this->role()) && $this->role() === "admin";
    }

    /**
     * Check if the current participant is a member.
     * @return bool
     */
    public function isMember(): bool
    {
        return !is_null($this->role()) && $this->role() === "member";
    }

    /**
     * Check if the current participant has left the conversation.
     * @return bool
     */
    public function hasLeft(): bool
    {
        return !is_null($this->leftAt());
    }

    /**
     * Promote the current participant to admin.
     * @return bool
     */
    public function promoteToAdmin(): bool
    {
        return $this->setRole("admin");
    }

    /**
     * Demote the current participant to member.
     * @return bool
     */
    public function demoteToMember(): bool
    {
        return $this->setRole("member");
    }

    /**
     * Exit from the group conversation.
     * @return bool
     */
    public function exit(): bool
    {
        $saved = $this->updatePivotValue([
            "left_at" => now(),
            "role" => null,
        ]);

        // fire the conversation left event
        if ($saved && \kareemsliet\Chat\Helper::eventsEnabled()) {
            event(new ConversationLeft($this->conversation));
        }

        return $saved;
    }

    /**
     * Alias for exit method.
     * @return bool
     */
    public function leave(): bool
    {
        return $this->exit();
    }

    /**
     * Add a single or multiple members to the group conversation.
     * 
     * @param mixed ...$members
     * @return array
     */
    public function addMember(...$members): array
    {
        return $this->addMembers($members);
    }

    /**
     * Add multiple members to the group conversation.
     *
     * return array of ids participant added to the group conversation
     * 
     * @param array|BaseCollection  $members
     * @return array
     */
    public function addMembers($members): array
    {
        // Parse participant IDs from the provided members
        $participantIds = $this->parseParticipantIds($members);

        // Sync participants to add or re-activate them to the conversation
        $participantIds = $this->attachOrUpdateExistingParticipants($participantIds, ["role" => "member", "left_at" => null]);

        // fire the participants joined event
        if (\kareemsliet\Chat\Helper::eventsEnabled() && !empty($participantIds)) {
            event(new ParticipantsJoined($this->conversation, $participantIds));
        }

        // return the participant IDs added
        return $participantIds;
    }

    /**
     * Remove a single member from the group conversation.
     *
     * @param mixed ...$members
     * @return array
     */
    public function removeMember(...$members): array
    {
        return $this->removeMembers($members);
    }

    /**
     * Remove multiple members from the group conversation.
     *
     * return array of ids participant removed from the group conversation
     * 
     * @param  array|BaseCollection  $members
     * @return array
     */
    public function removeMembers($members): array
    {
        // Parse participant IDs from the provided members
        $participantIds = $this->parseParticipantIds($members);

        // Update Existing Conversation participants to set their left_at timestamp and nullify their role
        $participantIds = $this->updateExistingParticipants($participantIds,["left_at" => now(), "role" => null]);

        // fire the participants left event
        if (\kareemsliet\Chat\Helper::eventsEnabled() && !empty($participantIds)) {
            event(new ParticipantsLeft($this->conversation, $participantIds));
        }

        // return the participant IDs removed
        return $participantIds;
    }

    /**
    * Make single or  multiple members as admin role in the conversation.
    *
    * return array of ids participant made as admin in the conversation
    *
    * @param mixed $members
    * @return array
    */
    public function makeAsAdmin($members): array
    {
        return $this->makeAs("admin",$members);
    }

    /**
     * make single or multiple members as Member role  in the conversation.
     * 
     * return array of ids participant made as member in the conversation
     * 
     * @param  mixed $members
     * @return array
     */
    public function makeAsMember($members): array
    {
        return $this->makeAs("member",$members);
    }

    // set role for single or multiple members in the conversation.
    protected function makeAs(string $role,mixed $members): array
    {
        // Parse participant IDs from the provided members
        $participantIds = $this->parseParticipantIds($members);

        // Update Existing Conversation participants to set their role
        $participantIds = $this->updateExistingParticipants($participantIds,["role" => $role]);

        // fire the participants role changed event
        if (\kareemsliet\Chat\Helper::eventsEnabled() && !empty($participantIds)) {
            event(new ParticipantsRoleChanged($this->conversation, $participantIds, $role));
        }

        // return the participant IDs removed
        return $participantIds;
    }

    // set the role for the current participant
    protected function setRole(string $role)
    {
        return $this->updatePivotValue(["role"=> $role ]);
    }
}