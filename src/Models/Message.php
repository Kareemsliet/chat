<?php

namespace kareemsliet\Chat\Models;

use kareemsliet\Chat\Builders\MessageBuilder;
use Illuminate\Database\Eloquent\Model;
use kareemsliet\Chat\Helper;

class Message extends Model
{
    protected $table = "messages";
    protected $fillable = [
        "conversation_id",
        "participant_id",
        "content",
        "attachments",
        "reply_message_id",
    ];

    protected $casts = [
        "attachments" => "array",
    ];

    protected $with = ["sender"];

    public function getIncrementing()
    {
        return !Helper::useUUIDForMessages();
    }

    public function getKeyType()
    {
        return Helper::useUUIDForMessages() ? 'string' : 'int';
    }

    protected static function booted()
    {
        if (Helper::useUUIDForMessages()) {
            static::creating(function ($model) {
                if (empty($model->{$model->getKeyName()})) {
                    $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::orderedUuid();
                }
            });
        }
    }

    public function sender()
    {
        return $this->belongsTo(Participant::class, "participant_id");
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, "conversation_id");
    }

    public function replyMessage()
    {
        return $this->belongsTo(static::class, "reply_message_id");
    }

    public function participantsBuilder()
    {
        return MessageBuilder::make($this->participants());
    }

    public function participants()
    {
        return $this->belongsToMany(Participant::class, "participant_has_message", "message_id", "participant_id")->withTimestamps();
    }
}