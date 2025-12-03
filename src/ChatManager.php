<?php

namespace kareemsliet\Chat;

use kareemsliet\Chat\Conversations\GroupConversation;
use kareemsliet\Chat\Conversations\PrivateConversation;
use kareemsliet\Chat\Exceptions\ParticipantException;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;
use Illuminate\Database\Eloquent\Model;
use kareemsliet\Chat\Services\MessageService;
use kareemsliet\Chat\Traits\HasParticipant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ChatManager
{
    /**
     * The current participant instance.
     *
     * @var Participant
     */
    protected Participant $participant;

    /**
     * Create a new chat manager instance.
     * @param Participant|null $participant
     * @return void
     */
    public function __construct(Participant|null $participant = null)
    {
        if (is_null($participant)) {
            if (!$user = request()->user()) {
                throw new ParticipantException("No participant provided and no authenticated user found. Use Chat::forAuth() or Chat::forUser(\$user) instead.");
            }
            $participant = $this->parseParticipant($user);
        }
        $this->participant = $participant;
    }

    /**
     * Create a chat for a specific participant.
     *
     * @param  Participant  $participant
     * @return static
     */
    public static function for(Participant $participant): static
    {
        return new static($participant);
    }

    /**
     * Create a chat for a user model.
     *
     * @param  Model  $model
     * @return static
     * @throws ParticipantException
     */
    public static function forUser(Model $model): static
    {
        if (!in_array(HasParticipant::class, class_uses_recursive($model))) {
            throw new ParticipantException(
                "Authenticated user model must use HasParticipant trait."
            );
        }
        return static::for($model->findOrCreateParticipant());
    }

    /**
     * Create a chat from current authenticated user.
     *
     * @param  string|null  $guard
     * @return static
     * @throws ParticipantException
     */
    public static function forAuth(?string $guard = null): static
    {
        $user = auth($guard)->user();

        if (!$user) {
            throw new ParticipantException('No participant provided and no authenticated user found.');
        }

        return static::forUser($user);
    }

    /**
     * Get the current participant.
     *
     * @return Participant
     */
    public function participant(): Participant
    {
        return $this->participant;
    }

    /**
     * Create a new group conversation.
     *
     * @param  array  $attributes
     * @return \kareemsliet\Chat\Conversations\GroupConversation
     */
    public function newGroup(array $attributes = []): GroupConversation
    {
        return GroupConversation::create($this->participant, $attributes);
    }

    /**
     * Create a new private conversation with the given user.
     *
     * @param  Model $user
     * @param  array  $attributes
     * @return PrivateConversation
     */
    public function newPrivate(Model $user, array $attributes = []): PrivateConversation
    {
        return PrivateConversation::create(
            $this->participant,
            $this->parseParticipant($user),
            $attributes
        );
    }

    /**
     * Find or create a private conversation with the given user.
     *
     * @param  Model $user
     * @param  array  $attributes
     * @return PrivateConversation
     */
    public function privateWith(Model $user, array $attributes = []): PrivateConversation
    {
        return PrivateConversation::setOrCreate(
            $this->participant,
            $this->parseParticipant($user),
            $attributes
        );
    }

    /**
     * Find a conversation by ID and return the appropriate handler.
     *
     * @param  string  $id
     * @return GroupConversation|PrivateConversation
     * @throws \UnexpectedValueException
     */
    public function findById(string $id):GroupConversation|PrivateConversation
    {
        return $this->find($this->parseConversation($id));
    }

    /**
     * Find a conversation and return the appropriate handler.
     *
     * @param  Conversation  $conversation
     * @return GroupConversation|PrivateConversation
     * @throws \UnexpectedValueException
     */
    public function find(Conversation $conversation): GroupConversation|PrivateConversation
    {
        return match ($conversation->type) {
            ConversationTypesEnum::Group => new GroupConversation($this->participant, $conversation),
            ConversationTypesEnum::Private => new PrivateConversation($this->participant, $conversation),
            default => throw new \UnexpectedValueException("Unsupported conversation type: {$conversation->type->value}"),
        };
    }

    /**
     * Get a private conversation by ID.
     *
     * @param  string  $id
     * @return PrivateConversation
     */
    public function privateById(string $id): PrivateConversation
    {
        return $this->private($this->parseConversation($id));
    }

    /**
     * Get a private conversation handler for the given conversation.
     *
     * @param  Conversation  $conversation
     * @return PrivateConversation
     */
    public function private(Conversation $conversation): PrivateConversation
    {
        return new PrivateConversation($this->participant, $conversation);
    }

    /**
     * Get a group conversation by ID.
     *
     * @param  string  $id
     * @return GroupConversation
     */
    public function groupById(string $id): GroupConversation
    {
        return $this->group($this->parseConversation($id));
    }

    /**
     * Get a group conversation handler for the given conversation.
     *
     * @param  Conversation  $conversation
     * @return GroupConversation
     */
    public function group(Conversation $conversation): GroupConversation
    {
        return new GroupConversation($this->participant, $conversation);
    }

    /**
     * Get all conversations for the current participant.
     *
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @return EloquentCollection
     */
    public function all(?callable $query = null,bool $useDefaultSorting = true): EloquentCollection
    { 
        $query = $this->query($query);

        if($useDefaultSorting){
            $query->orderByPinned()->orderByJoinDate()->orderByLatestMessage();
        }

        return $query->get();
    }

    /**
     * Get conversations with a specific user.
     *
     * @param  Model $user
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @return EloquentCollection
     */
    public function withParticipant(Model $user, ?callable $query = null, bool $useDefaultSorting = true): EloquentCollection
    {
        $participant = $this->parseParticipant($user);

        return $this->query(function ($builder) use ($participant, $query,$useDefaultSorting) {

            $builder->whereParticipant($participant);

            if ($query) {
                $query($builder);
            }
            
            if($useDefaultSorting){
                $builder->orderByPinned()->orderByJoinDate()->orderByLatestMessage();
            }

        })->get();
    }

    /**
     * Get unread conversations.
     *
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @return EloquentCollection
     */
    public function unread(?callable $query = null, bool $useDefaultSorting = true): EloquentCollection
    {
        return $this->query(function ($builder) use ($query,$useDefaultSorting) {

            $builder->whereUnread();

            if ($query) {
                $query($builder);
            }

            if($useDefaultSorting){
                $builder->orderByPinned()->orderByJoinDate()->orderByLatestMessage();
            }

        })->get();
    }

    /**
     * Get favorited conversations.
     *
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @return EloquentCollection
     */
    public function favorited(?callable $query = null, bool $useDefaultSorting = true): EloquentCollection
    {
        return $this->query(function ($builder) use ($query,$useDefaultSorting) {

            $builder->favorited();

            if ($query) {
                $query($builder);
            }

            if($useDefaultSorting){
                $builder->orderByPinned()->orderByJoinDate()->orderByLatestMessage();
            }

        })->get();
    }

    /**
     * Get pinned conversations.
     *
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @return EloquentCollection
     */
    public function pinned(?callable $query = null, bool $useDefaultSorting = true): EloquentCollection
    {
        return $this->query(function ($builder) use ($query,$useDefaultSorting) {

            $builder->pinned();

            if ($query) {
                $query($builder);
            }

            if($useDefaultSorting){
                $builder->orderByJoinDate()->orderByLatestMessage();
            }

        })->get();
    }

    /**
     * Paginate the conversations.
     *
     * @param  int  $perPage
     * @param  callable|null  $query
     * @param  bool  $useDefaultSorting
     * @param  array  $options // Pagination options like 'page_name', and 'page'
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, ?callable $query = null, bool $useDefaultSorting = true, array $options = [])
    {
        $builder = $this->query($query);

        if($useDefaultSorting){
            $builder->orderByPinned()->orderByJoinDate()->orderByLatestMessage();
        }

        // Default page name is 'page'
        $pageName = $options['page_name'] ?? 'page';

        // Default page is null
        $page = $options['page'] ?? null;

        return $builder->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Get a conversation query builder.
     *
     * @param  callable|null  $callback
     * @return \kareemsliet\Chat\Builders\ConversationBuilder
     */
    public function query(?callable $callback = null): \kareemsliet\Chat\Builders\ConversationBuilder
    {
        // Get the base conversation builder
        $builder = $this->participant->conversationsBuilder();

        // Apply custom query modifications
        if ($callback) {
            $callback($builder);
        }

        // Return the configured builder
        return $builder;
    }

    /**
     * Get message for a specific participant.
     *
     * @param Message $message
     * @return MessageService
     */
    public function message(Message $message): MessageService
    {
        return new MessageService($this->participant, $message);
    }

    /**
     * Get message by ID for the current participant.
     *
     * @param string $id
     * @return MessageService
     */
    public function messageById(string $id): MessageService
    {
        return $this->message($this->parseMessage($id));
    }

    /**
     * Get all messages for the current participant.
     *
     * @param  callable|null  $callback
     * @return EloquentCollection
     */
    public function messages(?callable $callback = null): EloquentCollection
    {
        return $this->messagesQuery($callback)->orderByCreatedAt()->get();
    }

    /**
     * Get all starred messages for the current participant.
     *
     * @param  callable|null  $callback
     * @return EloquentCollection
     */
    public function starredMessages(?callable $callback = null): EloquentCollection
    {
        return $this->messagesQuery(function($query)use($callback){

            $query->starred();

            if($callback){
                $callback($query);
            }

        })->orderByStarredAt()->get();
    }

    /**
     * Get messages query for current participant.
     * @param  callable|null  $callback
     * @return \kareemsliet\Chat\Builders\MessageBuilder
     */
    public function messagesQuery(?callable $callback = null): \kareemsliet\Chat\Builders\MessageBuilder
    {
        $builder = $this->participant->messagesBuilder();

        // Apply custom query modifications
        if ($callback) {
            $callback($builder);
        }

        return $builder;
    }

    protected function parseParticipant(Model $member): Participant
    {
        // If already a Participant instance
        if ($member instanceof Participant) {
            return $member;
        }

        // Ensure the model uses the HasParticipant trait
        if (!in_array(HasParticipant::class, class_uses_recursive($member))) {
            throw new ParticipantException('user Model must use HasParticipant trait.');
        }

        // Return the Participant instance , creating it if necessary
        return $member->findOrCreateParticipant();
    }

    protected function parseConversation(string $conversation): Conversation
    {
        return $this->participant->conversationsBuilder()->findOr(
            $conversation,
            ['*'],
            fn() => throw new \kareemsliet\Chat\Exceptions\ConversationException("No conversation found with identifier: {$conversation} for participant ID: {$this->participant->id}")
        );
    }

    protected function parseMessage(string $message): Message
    {
        return $this->participant->messagesBuilder()->findOr(
            $message,
            ['*'],
            fn() => throw new \kareemsliet\Chat\Exceptions\MessageException("No message found with the given identifier: {$message}")
        );
    }
}