<?php

namespace kareemsliet\Chat\Concerns;

trait InteractsWithPivotColumns
{
    protected array $pivot = [];

    abstract protected function getPivot():array;

    abstract protected function updatePivot(array $attributes):bool;

    /**
     * Get a specific column value from the pivot table.
     *
     * @param string $column
     * @param mixed $default
     * @return mixed
     */
    public function getPivotValue(string $column, $default = null)
    {
        if (array_key_exists($column, $this->pivot)) {
            return $this->pivot[$column];
        }

        $this->pivot = $this->getPivot();

        return array_key_exists($column,$this->pivot) ? $this->pivot[$column] : $default;
    }

    /**
     * Update specific columns in the pivot table.
     *
     * @param array $attributes
     * @return bool
     */
    public function updatePivotValue(array $attributes = []): bool
    {
        $updated = $this->updatePivot($attributes);

        if (!$updated) {
           return $updated;
        }

        $this->pivot = array_merge($this->pivot, $attributes);
        
        return $updated;
    }

    /**
     * excute a callback based when a pivot column has a value.
     *
     * @param string $column
     * @param callable $callback
     * @param callable|null $default
     * @return mixed
     */
    public function whenPivotValue(string $column, callable $callback, ?callable $default = null)
    {
        $value = $this->getPivotValue($column);

        if ($value) {
            return $callback($value);
        }

        return is_callable($default) ? $default($value) : null;
    }

    /**
     * Clear the cached pivot data.
     *
     * @return void
     */
    public function clearPivot(): void
    {
        $this->pivot = [];
    }

    /**
     * Refresh the cached pivot data.
     *
     * @return void
     */
    public function refreshPivot(): void
    {
        $this->pivot = $this->getPivot();
    }
}

