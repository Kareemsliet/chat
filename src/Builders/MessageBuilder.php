<?php

namespace kareemsliet\Chat\Builders;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Traits\Conditionable;

class MessageBuilder extends BelongsToMany
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
            "is_sender",
            "read_at",
            "starred_at",
            "created_at",
            "updated_at",
        ]);

        return $builder;
    }

    public function addConstraints()
    {
        parent::addConstraints();

        if (static::$constraints) {
            //
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

    public function update(array $values): bool
    {
        return parent::update($values) > 0;
    }

    public function query(): static
    {
        return $this;
    }

    public function read(): static
    {
        return $this->wherePivotNotNull('read_at');
    }

    public function unread(): static
    {
        return $this->wherePivotNull('read_at');
    }

    public function sent(): static
    {
        return $this->wherePivot('is_sender', 1);
    }

    public function received(): static
    {
        return $this->wherePivot('is_sender', 0);
    }

    public function starred(): static
    {
        return $this->wherePivotNotNull('starred_at');
    }

    public function unstarred(): static
    {
        return $this->wherePivotNull('starred_at');
    }

    public function orderByReadAt(string $direction = "desc"): static
    {
        return $this->orderByPivot("read_at", $direction);
    }

    public function orderByDeliveredAt(string $direction = 'desc'): static
    {
        return $this->orderByPivot('created_at', $direction);
    }

    public function orderByStarredAt(string $direction = 'desc'): static
    {
        return $this->orderByPivot('starred_at', $direction);
    }

    public function orderByCreatedAt(string $direction = 'desc'): static
    {
        return $this->orderBy("created_at", $direction);
    }

    public function sentAfter(\DateTimeInterface|string $date): static
    {
        return $this->where($this->parseColumn("created_at"), '>', $date);
    }

    public function sentBefore(\DateTimeInterface|string $date): static
    {
        return $this->where($this->parseColumn("created_at"), '<', $date);
    }

    protected function parseColumn(string $column): string
    {
        if (in_array($column, ['id', 'created_at', 'updated_at'])) {
            return $this->getRelated()->getTable() . '.' . $column;
        }

        return $column;
    }
}
