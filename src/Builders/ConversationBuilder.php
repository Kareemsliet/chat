<?php

namespace kareemsliet\Chat\Builders;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Traits\Conditionable;
use kareemsliet\Chat\Models\Message;
use kareemsliet\Chat\Models\Participant;

class ConversationBuilder extends BelongsToMany
{
    use Conditionable;

    public static function make(BelongsToMany $relation): self
    {
        $builder = new self(
            $relation->getRelated()->newQuery(),
            $relation->getParent(),
            $relation->getTable(),
            $relation->getForeignPivotKeyName(),
            $relation->getRelatedPivotKeyName(),
            $relation->getParentKeyName(),
            $relation->getRelatedKeyName(),
        );

        $builder->withPivot([
            'is_pinned',
            'is_favorited',
            'left_at',
            "role",
            "updated_at",
            'created_at',
        ]);

        return $builder;
    }

    public function addConstraints()
    {
        parent::addConstraints();

        if (static::$constraints) {
           
        }
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::where($column, $operator, $value, $boolean);

        return $this;
    }

    public function whereNot($column, $operator = null, $value = null, $boolean = 'and')
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::whereNot($column, $operator, $value, $boolean);

        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::orWhere($column, $operator, $value);

        return $this;
    }

    public function orWhereNot($column, $operator = null, $value = null)
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::orWhereNot($column, $operator, $value);

        return $this;
    }

    public function whereIn($column, array $values, string $boolean = 'and', bool $not = false): static
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::whereIn($column, $values, $boolean, $not);

        return $this;
    }

    public function orderBy($column, string $direction = 'asc'): static
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        parent::orderBy($column, $direction);

        return $this;
    }

    public function pluck($column, $key = null)
    {
        $column = is_string($column) ? $this->parseColumn($column) : $column;

        $key = is_string($key) ? $this->parseColumn($key) : $key;

        return parent::pluck($column, $key);
    }

    public function query(): static
    {
        return $this;
    }


    public function ofRole(string $role) : static
    {
        return $this->wherePivot("role",'=',$role);
    }

    public function admins() : static
    {
        return $this->ofRole("admin");
    }

    public function members(): static
    {
        return $this->ofRole("member");
    }

    public function pinned(): static
    {
        return $this->wherePivot('is_pinned', 1);
    }

    public function unpinned(): static
    {
        return $this->wherePivot('is_pinned', 0);
    }

    public function favorited(): static
    {
        return $this->wherePivot('is_favorited', 1);
    }

    public function unfavorited(): static
    {
        return $this->wherePivot('is_favorited', 0);
    }

    public function active(): static
    {
        return $this->wherePivotNull('left_at');
    }

    public function inactive(): static
    {
        return $this->wherePivotNotNull('left_at');
    }

    public function orderByCreatedAt(string $direction = 'desc'): static
    {
        return $this->orderBy("created_at", $direction);
    }

    public function orderByPinned(string $direction = 'desc'): static
    {
        return $this->orderByPivot('is_pinned', $direction);
    }

    public function orderByJoinDate(string $direction = 'desc'): static
    {
        return $this->orderByPivot('created_at', $direction);
    }

    public function orderByLeftDate(string $direction = 'desc'): static
    {
        return $this->orderByPivot('left_at', $direction);
    }

    public function whereLeftBefore(string|\DateTimeInterface $value, string $operator = "<", $boolean = 'and'): static
    {
        return $this->wherePivot("left_at", $operator, $value, $boolean);
    }

    public function whereLeftAfter(string|\DateTimeInterface $value, string $operator = ">", $boolean = 'and'): static
    {
        return $this->wherePivot("left_at", $operator, $value, $boolean);
    }

    public function whereJoinedBefore(string|\DateTimeInterface $value, string $operator = "<", $boolean = 'and'): static
    {
        return $this->wherePivot("created_at", $operator, $value, $boolean);
    }

    public function whereJoinedAfter(string|\DateTimeInterface $value, string $operator = ">", $boolean = 'and'): static
    {
        return $this->wherePivot("created_at", $operator, $value, $boolean);
    }

    /**
     * filter by participant.
     * 
     * for conversation related model.
     * @param Participant|string $participant
     * @return static
     */
    public function whereParticipant(Participant|string $participant): static
    {
        $participantId = $participant instanceof Participant ? $participant->id : $participant;

        return $this->whereHas('participants', function ($query) use ($participantId) {
            $query->where('participants.id', $participantId);
        });
    }

    /**
     * filter unread conversations for current participant.
     *
     * for conversations related table columns.
     * @return static
     */
    public function whereUnread(): static
    {
        $participantMessageTable = (new Participant())->messages()->getTable();

        $messagesTable = (new Message())->getTable();

        $conversationsTable = $this->getRelated()->getTable();

        return $this->whereExists(function ($query) use ($participantMessageTable, $messagesTable, $conversationsTable) {
            $query->select(DB::raw(1))
            ->from($participantMessageTable)
            ->join($messagesTable, "{$participantMessageTable}.message_id", '=', "{$messagesTable}.id")
            ->whereColumn("{$messagesTable}.conversation_id", "{$conversationsTable}.id")
            ->where("{$participantMessageTable}.participant_id", $this->getParent()->id)
            ->where("{$participantMessageTable}.is_sender", 0)
            ->whereNull("{$participantMessageTable}.read_at")
            ->limit(1);
        });
    }

    /**
     * Order by latest message.
     *
     * for conversations related table columns.
     * @param string $direction
     * @return static
     */
    public function orderByLatestMessage(string $direction = "desc"): static
    {
        return $this->orderBy(
            DB::table('messages')->selectRaw('MAX(created_at)')->whereColumn('messages.conversation_id', 'conversations.id'),
            $direction
        );
    }

    /**
     * eager load latest message relation.
     *
     * for conversations related table columns.
     * @return static
     */
    public function withLatestMessageRelation():static
    {
        return $this->with("latestMessage",function($query){
            $query->whereHas('participants', function ($query) {
                $query->where('participants.id','=',$this->getParent()->getKey());
            });
        });
    }

    protected function parseColumn(string $column): string
    {
        if (in_array($column, ['id', 'created_at', 'updated_at'])) {
            return $this->getRelated()->getTable() . '.' . $column;
        }
        
        return $column;
    }
}
