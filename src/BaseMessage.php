<?php

namespace kareemsliet\Chat;

use DateTime;
use Illuminate\Database\Eloquent\Collection as ElquentCollection;
use kareemsliet\Chat\Concerns\InteractsWithPivotColumns;
use kareemsliet\Chat\Contracts\MessageContract;
use kareemsliet\Chat\Events\MessageWasEdited;
use kareemsliet\Chat\Models\Conversation;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;

abstract class BaseMessage implements MessageContract
{
    use InteractsWithPivotColumns;

    /**
     * The participant instance.
     *
     * @var Participant
     */
    public Participant $participant;

    /**
     * The message instance.
     *
     * @var Message
     */
    public Message $message;

    /**
     * The sender instance.
     *
     * @var Participant|null
     */
    private ?Participant $sender = null;

    /**
     * The message reply instance.
     *
     * @var Message|null
     */
    private ?Message $replyMessage = null;

    /**
     * The conversation instance.
     *
     * @var Conversation|null
     */
    private ?Conversation $conversation = null;

    public function __construct(Participant $participant, Message $message)
    {
        $this->message = $message;

        $this->participant = $participant;

        if ($this->message->relationLoaded('pivot')) {
            $this->pivot = $this->message->pivot->toArray();
        }

        if (!$this->belongsToMessage()) {
            throw new \kareemsliet\Chat\Exceptions\MessageException("Participant does not belong to this message.");
        }
    }

    protected function belongsToMessage()
    {
        if (array_key_exists("participant_id", $this->pivot)) {
            return $this->pivot["participant_id"] === $this->participant->getKey();
        }

        return $this->participant->messagesBuilder()->where("id", $this->message->getKey())->exists();
    }

    public function participants(callable|null $query = null): ElquentCollection
    {
        return $this->participantsBuilder($query)->get();
    }

    public function readBy(): ElquentCollection
    {
        return $this->participants(fn($query) => $query->received()->read());
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getSender(): Participant
    {
        if (is_null($this->sender)) {
            $this->sender = $this->message->sender;
        }

        return $this->sender;
    }

    public function getReplyMessage(): ?Message
    {
        if (is_null($this->replyMessage)) {
            $this->replyMessage = $this->message->replyMessage;
        }

        return $this->replyMessage;
    }

    public function getConversation(): Conversation
    {
        if (is_null($this->conversation)) {
            $this->conversation = $this->message->conversation;
        }

        return $this->conversation;
    }

    public function isSender(): bool
    {
        return (bool) $this->getPivotValue("is_sender", 0);
    }

    public function isStarred(): bool
    {
        return !is_null($this->getPivotValue('starred_at', null));
    }

    public function createdAt(): DateTime
    {
        return new DateTime($this->message->created_at);
    }

    public function updatedAt(): DateTime
    {
        return new DateTime($this->message->updated_at);
    }

    public function starredAt(): ?DateTime
    {
        return $this->whenPivotValue('starred_at', fn($value) => new DateTime($value));
    }

    public function deliveredAt(): ?DateTime
    {
        return $this->whenPivotValue('created_at', fn($value) => new DateTime($value));
    }

    public function markAsRead()
    {
        if ($this->isSender()) {
            return;
        }

        return $this->updatePivotValue(['read_at' => now()]);
    }

    public function star(): bool
    {
        return $this->updatePivotValue(["starred_at" => now()]);
    }

    public function unstar(): bool
    {
        return $this->updatePivotValue(["starred_at" => null]);
    }

    public function toggleStar(): bool
    {
        return $this->updatePivotValue(['starred_at' => $this->isStarred() ? null : now()]);
    }

    public function hasReply(): bool
    {
        return !is_null($this->getReplyMessage());
    }

    public function replyMessage(): static
    {
        return new static($this->participant, $this->getReplyMessage());
    }

    public function getAgeIn(string $unit = "day"): float
    {
        $createdAt = \Carbon\Carbon::parse($this->createdAt());

        $date = now();

        $diff = match ($unit) {
            'year' => $createdAt->diffInYears($date),
            'month' => $createdAt->diffInMonths($date),
            'week' => $createdAt->diffInWeeks($date),
            'day' => $createdAt->diffInDays($date),
            'hour' => $createdAt->diffInHours($date),
            'minute' => $createdAt->diffInMinutes($date),
            'second' => $createdAt->diffInSeconds($date),
            default => throw new \UnexpectedValueException("Invalid unit: $unit. Allowed units are year, month, week, day, hour, minute, second."),
        };

        return round($diff, 2);
    }

    public function deleteForMe(): bool
    {
        return $this->participant->messages()->detach($this->message->id) > 0;
    }

    public function deleteForMeIn(string $unit = "day", int|float $maxAge = 1): bool
    {
        $messageAge = $this->getAgeIn($unit);

        if ($messageAge <= $maxAge) {
            return $this->deleteForMe();
        }

        return false;
    }

    public function delete(): bool|null
    {
        return $this->message->deleteOrFail();
    }

    public function deleteIn(string $unit = "day", int|float $maxAge = 1): bool|null
    {
        $messageAge = $this->getAgeIn($unit);

        if ($messageAge <= $maxAge) {
            return $this->delete();
        }

        return false;
    }

    public function edit(string $newContent): bool
    {
        $this->message->content = $newContent;

        $saved = $this->message->save();

        if ($saved && \kareemsliet\Chat\Helper::broadcastEnabled()) {
            MessageWasEdited::broadcast($this->message)->toOthers();
        }

        return $saved;
    }

    public function editIn(string $newContent, string $unit = "day", int|float $maxAge = 1): bool
    {
        $messageAge = $this->getAgeIn($unit);

        if ($messageAge <= $maxAge) {
            return $this->edit($newContent);
        }

        return false;
    }

    public function clone(): static
    {
        return clone $this;
    }

    public function participantsBuilder(?callable $query = null): \kareemsliet\Chat\Builders\MessageBuilder
    {
        return $this->message->participantsBuilder()
            ->when(is_callable($query), fn($builder) => $query($builder))
            ->query();
    }

    protected function getPivot(): array
    {
        $message = $this->participant->messagesBuilder()->where('id', $this->message->id)->first();

        if ($message && $message->relationLoaded('pivot')) {
            return $message->pivot->toArray();
        }

        return [];
    }

    protected function updatePivot(array $attributes): bool
    {
        $affected = $this->participant->messages()->updateExistingPivot(
            $this->message->id,
            $attributes,
        );

        return $affected > 0 ? true : false;
    }
}

