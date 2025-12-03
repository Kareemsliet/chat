<?php

namespace kareemsliet\Chat\Models;

use kareemsliet\Chat\Builders\ConversationBuilder;
use Illuminate\Database\Eloquent\Model;
use kareemsliet\Chat\Helper;
use kareemsliet\Chat\ConversationTypesEnum;

class Conversation extends Model
{
    protected $table = "conversations";
    protected $fillable = [
        "type",
        "description",
        "title",
        "image",
    ];

    public function getIncrementing()
    {
        return !Helper::useUUIDForConversations();
    }

    public function getKeyType()
    {
        return Helper::useUUIDForConversations() ? 'string' : 'int';
    }

    protected static function booted()
    {
        if (Helper::useUUIDForConversations()) {
            static::creating(function ($model) {
                if (empty($model->{$model->getKeyName()})) {
                    $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::orderedUuid();
                }
            });
        }
    }

    protected function casts()
    {
        return [
            "type" => ConversationTypesEnum::class,
        ];
    }

    public function messages()
    {
        return $this->hasMany(Message::class, "conversation_id");
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class, "conversation_id")->latest('created_at');
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class, "participant_has_conversation", "conversation_id", "participant_id")->withTimestamps();
    }

    public function participantsBuilder()
    {
        return ConversationBuilder::make($this->participants());
    }

    public function scopeGroup($query)
    {
        return $query->where('type', ConversationTypesEnum::Group);
    }

    public function scopePrivate($query)
    {
        return $query->where('type', ConversationTypesEnum::Private);
    }
}