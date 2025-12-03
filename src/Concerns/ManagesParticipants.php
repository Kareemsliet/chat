<?php

namespace kareemsliet\Chat\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use kareemsliet\Chat\Models\Participant;
use kareemsliet\Chat\Traits\HasParticipant;

trait ManagesParticipants
{
    protected function parseParticipantIds(mixed $value): array
    {
        if ($value instanceof Participant) {
            return [$value->getKey()];
        }

        if ($value instanceof Model) {
            return [$this->resolveParticipantId($value)];
        }

        if ($value instanceof Collection || is_array($value)) {
            return collect($value)->map(fn($item) => $this->resolveParticipantId($item))->whereNotNull()->all();
        }

        if (is_scalar($value)) {
            return [(int) $value];
        }

        return (array) $value;
    }

    protected function resolveParticipantId(mixed $item): int|string|null
    {
        if ($item instanceof Participant) {
            return $item->getKey();
        }

        if ($item instanceof Model && in_array(HasParticipant::class, class_uses_recursive($item))) {
            return $item->findOrCreateParticipant()->getKey();
        }

        if (is_scalar($item)) {
            return $item;
        }

        return null;
    }

    protected function attachOrUpdateExistingParticipants(array $ids, array $attributes = []): array
    {
        // Get existing participant IDs in the conversation
        $existing = $this->getConversation()->participantsBuilder()->whereIn('id', $ids)->pluck("id")->all();
        
        // Determine new participant IDs to add
        $new = array_diff($ids, $existing);

        // Update existing participants with the given attributes
        if (!empty($existing)) {
            $this->getConversation()->participants()->updateExistingPivot($existing, $attributes);
        }

        // Add new participants with the given attributes
        if (!empty($new)) {
            $this->getConversation()->participants()->attach($new, $attributes);
        }

        // Return all participant IDs after sync
        return array_merge($existing, $new);
    }

    protected function updateExistingParticipants(array $ids, array $attributes = []): array
    {
        // Get existing participant IDs in the conversation
        $existing = $this->getConversation()->participantsBuilder()->whereIn('id', $ids)->pluck('id')->all();

        // Update existing participants with the given attributes
        if(!empty($existing))
        {
            $this->getConversation()->participants()->updateExistingPivot($existing, $attributes);
        }

        // return existing participant IDs
        return $existing;
    }
}