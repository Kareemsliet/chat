<?php

namespace kareemsliet\Chat;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chat.php','chat');

        $this->registerChat();
    }

    /**
     * Register the chat manager.
     */
    protected function registerChat()
    {
        $this->app->bind("chat",function($app){
            return new \kareemsliet\Chat\ChatManager();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/create_chat_tables.php' => database_path("migrations") . '/' . date('Y_m_d_His', time()) . '_create_chat_table.php',
        ], ["chat", "chat-migrations"]);

        $this->publishes([
            __DIR__ . '/../config/chat.php' => config_path("chat.php"),
        ], ["chat","chat-config"]);

        $this->defineBroadcastChannels();
    }

    protected function defineBroadcastChannels(): void
    {
        Broadcast::channel("chat.conversations.{id}",ConversationChannel::class);
    }
}
