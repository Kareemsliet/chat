<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use kareemsliet\Chat\Helper;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {

            if (Helper::useUUIDForConversations()) {
                $table->uuid("id")->primary();
            } else {
                $table->id();
            }

            $table->string("type")->default("private")->index();

            $table->longText("description")->nullable();

            $table->string("title")->nullable()->index();

            $table->string("image")->nullable();

            $table->timestamps();
        
        });

        Schema::create("participants", function (Blueprint $table) {

            $table->id();

            $table->morphs("participantable", "participantable_index");

            $table->timestamps();

            $table->index(["participantable_id", "participantable_type"], "participantable_composite_index");

        });

        Schema::create("participant_has_conversation", function (Blueprint $table) {

            $table->id();

            $table->foreignId("participant_id")->constrained("participants")->onDelete("restrict");

            if (Helper::useUUIDForConversations()) {
                $table->foreignUuid("conversation_id")->constrained("conversations")->onDelete("cascade");
            } else {
                $table->foreignId("conversation_id")->constrained("conversations")->onDelete("cascade");
            }

            $table->tinyInteger("is_pinned")->default(0);

            $table->tinyInteger("is_favorited")->default(0);

            $table->timestamp("left_at")->nullable();

            $table->string("role")->nullable();

            $table->timestamps();

        });

        Schema::create("messages", function (Blueprint $table) {

            if (Helper::useUUIDForMessages()) {
                $table->uuid("id")->primary();
            } else {
                $table->id();
            }

            if (Helper::useUUIDForConversations()) {
                $table->foreignUuid("conversation_id")->constrained("conversations")->onDelete("cascade");
            } else {
                $table->foreignId("conversation_id")->constrained("conversations")->onDelete("cascade");
            }

            $table->foreignId("participant_id")->constrained("participants")->onDelete("cascade");

            $table->longText("content")->nullable();

            $table->longText("attachments")->nullable();

            $table->timestamps();

            $table->index("created_at");

        });

        Schema::create("participant_has_message", function (Blueprint $table) {

            $table->id();

            $table->foreignId("participant_id")->constrained("participants")->onDelete("cascade");

            if (Helper::useUUIDForMessages()) {
                $table->foreignUuid("message_id")->constrained("messages")->onDelete("cascade");
            } else {
                $table->foreignId("message_id")->constrained("messages")->onDelete("cascade");
            }

            $table->tinyInteger("is_sender")->default(0);

            $table->timestamp("starred_at")->nullable();

            $table->timestamp("read_at")->nullable();

            $table->timestamps();

        });

        Schema::table("messages", function (Blueprint $table) {
            if (Helper::useUUIDForMessages()) {
                $table->foreignUuid("reply_message_id")->nullable()->constrained("messages")->nullOnDelete();
            } else {
                $table->foreignId("reply_message_id")->nullable()->constrained("messages")->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_has_message');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('participant_has_conversation');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('conversations');
    }
};
