<?php

namespace kareemsliet\Chat;

use kareemsliet\Chat\Concerns\InteractsWithPivotColumns;
use kareemsliet\Chat\Contracts\ConversationContract;
use kareemsliet\Chat\Events\ConversationCleared;
use kareemsliet\Chat\Events\ConversationUpdated;
use kareemsliet\Chat\Facades\Chat;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use kareemsliet\Chat\Services\MessageService;

abstract class BaseConversation implements ConversationContract
{
    use InteractsWithPivotColumns;
    public Conversation $conversation;
    public Participant $participant;
    public function __construct(Participant $participant, Conversation $conversation)
    {
        $this->participant = $participant;

        $this->conversation = $conversation;

        if ($this->conversation->relationLoaded('pivot')) {
            $this->pivot = $this->conversation->pivot->toArray();
        }

        if (!$this->belongsToConversation()) {
            throw new \kareemsliet\Chat\Exceptions\ConversationException("Participant does not belong to this conversation.");
        }
    }

    protected function belongsToConversation()
    {
        if (array_key_exists("participant_id", $this->pivot)) {
            return $this->pivot["participant_id"] === $this->participant->getKey();
        }

        return $this->participant->conversationsBuilder()->where("id", $this->conversation->getKey())->exists();
    }

    public function getConversation(): Conversation
    {
        return $this->conversation;
    }

    public function createdAt(): \DateTime
    {
        return new \DateTime($this->conversation->created_at);
    }

    public function type(): string
    {
        return $this->conversation->type->value;
    }

    public function isGroup(): bool
    {
        return $this->type() === (string) \kareemsliet\Chat\ConversationTypesEnum::Group->value;
    }

    public function isPinned(): bool
    {
        return (bool) $this->getPivotValue('is_pinned', 0);
    }

    public function pin(): bool
    {
        return $this->updatePivotValue(['is_pinned' => 1]);
    }

    public function unpin(): bool
    {
        return $this->updatePivotValue(['is_pinned' => 0]);
    }

    public function togglePin(): bool
    {
        return $this->updatePivotValue(['is_pinned' => $this->isPinned() ? 0 : 1]);
    }

    public function isFavorited(): bool
    {
        return (bool) $this->getPivotValue('is_favorited', 0);
    }

    public function favorite(): bool
    {
        return $this->updatePivotValue(['is_favorited' => 1]);
    }

    public function unfavorite(): bool
    {
        return $this->updatePivotValue(['is_favorited' => 0]);
    }

    public function toggleFavorite(): bool
    {
        return $this->updatePivotValue(['is_favorited' => $this->isFavorited() ? 0 : 1]);
    }

    public function joinedAt(): \DateTime|null
    {
        return $this->whenPivotValue("created_at", fn($value) => new \DateTime($value));
    }

    public function participants(callable|null $query = null): EloquentCollection
    {
        return $this->participantsBuilder($query)->get();
    }

    public function update(array $attributes): bool
    {
        $saved = $this->conversation->update($attributes);

        if ($saved && \kareemsliet\Chat\Helper::eventsEnabled()) {
            event(new ConversationUpdated(
                $this->conversation,
                $attributes
            ));
        }

        return $saved;
    }

    public function deleteForMe(): bool
    {
        return $this->participant->conversations()->detach($this->conversation->id) > 0;
    }

    public function delete(): bool
    {
        return $this->conversation->deleteOrFail();
    }

    public function messageById(string $id): MessageService
    {
        $message = $this->messagesBuilder()->where('id', $id)->first();

        if (!$message) {
            throw new \kareemsliet\Chat\Exceptions\MessageException("Message not found in this conversation.");
        }

        return Chat::for($this->participant)->message($message);
    }

    public function message(Message $message): MessageService
    {
        if ($message->conversation_id !== $this->conversation->id) {
            throw new \kareemsliet\Chat\Exceptions\MessageException("Message does not belong to this conversation.");
        }

        return Chat::for($this->participant)->message($message);
    }

    public function sendMessage(string $content, array $attachments = [], Message|string|null $reply = null): MessageService
    {
        return MessageService::send($this->conversation, $this->participant, $content, $attachments, $reply);
    }

    public function latestMessage(): ?MessageService
    {
        // check if latestMessage relation is loaded
        if($this->conversation->relationLoaded("latestMessage"))
        {
            $latestMessage = $this->conversation->latestMessage;
        }else{
            $latestMessage = $this->messagesBuilder()->latest()->first();
        }

        // Return null if no message found
        if (is_null($latestMessage)) {
            return null;
        }

        return Chat::for($this->participant)->message($latestMessage);
    }

    public function markAsRead(): bool
    {
        return $this->messagesBuilder()->received()->unread()->update(['read_at' => now()]);
    }

    public function clear(): bool
    {
        $messageIds = $this->messagesBuilder()->pluck("id")->all();

        if (empty($messageIds)) {
            return false;
        }

        $detached = $this->participant->messages()->detach($messageIds) > 0;

        if ($detached && \kareemsliet\Chat\Helper::eventsEnabled()) {
            event(new ConversationCleared($this->conversation));
        }

        return $detached;
    }

    public function unreadCount(): int
    {
        return $this->messagesBuilder()->received()->unread()->count();
    }

    public function messages(?callable $query = null, ?int $offset = null, ?int $limit = null): EloquentCollection
    {
        $builder = $this->messagesBuilder()->orderByCreatedAt()->query();

        if (is_callable($query)) {
            $query($builder);
        }

        if (is_int($offset)) {
            $builder->skip($offset);
        }

        if (is_int($limit)) {
            $builder->take($limit);
        }

        return $builder->get();
    }

    public function messagesPaginated(int $perPage = 15, ?callable $query = null, array $options = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $builder = $this->messagesBuilder()->orderByCreatedAt()->query();

        if ($query) {
            $query($builder);
        }

        // Default page name is 'page'
        $pageName = $options['page_name'] ?? 'page';

        // Default page is null
        $page = $options['page'] ?? null;

        return $builder->paginate($perPage, ['*'], $pageName, $page);
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function messagesBuilder(): \kareemsliet\Chat\Builders\MessageBuilder
    {
        return $this->participant->messagesBuilder()
            ->where("conversation_id", '=', $this->conversation->id)
            ->query();
    }

    public function participantsBuilder(?callable $query = null): \kareemsliet\Chat\Builders\ConversationBuilder
    {
        return $this->conversation->participantsBuilder()
            ->when(is_callable($query), fn($subQuery) => $query($subQuery))
            ->query();
    }

    protected function getPivot(): array
    {
        $conversation = $this->participant->conversationsBuilder()
            ->where('id', $this->conversation->id)
            ->first();

        if ($conversation && $conversation->relationLoaded('pivot')) {
            return $conversation->pivot->toArray();
        }

        return [];
    }

    protected function updatePivot(array $attributes): bool
    {
        $affected = $this->participant->conversations()->updateExistingPivot(
            $this->conversation->id,
            $attributes
        );

        return $affected > 0 ? true : false;
    }
}