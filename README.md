# 💬 Laravel Chat

Modern chat package for Laravel with private conversations, group chats, and message management.

## Features

- 💬 Private & group conversations
- 📨 Message management (send, edit, delete, reply)
- 🔔 Real-time broadcasting support (built-in)
- 👥 Participant roles (admin, moderator, member)
- 📌 Pin, favorite & star conversations
- 🔐 End-to-end encryption (RSA-2048 + AES-256)
- 🆔 UUID support
- 📎 File attachments
- ✅ Read receipts

## Requirements

- PHP 8.1+
- Laravel 10.x | 11.x | 12.x

## Installation

```bash
composer require kareemsliet/chat
php artisan vendor:publish --tag=chat-migrations
php artisan migrate
```

## Setup

Add trait to your User model:

```php
use kareemsliet\Chat\Traits\HasParticipant;

class User extends Authenticatable
{
    use HasParticipant;
}
```

## Quick Start

**Create Conversations:**

```php
use kareemsliet\Chat\Facades\Chat;

// Private conversation
$conversation = Chat::privateWith($otherUser);

// Group conversation
$conversation = Chat::newGroup(['title' => 'Team Chat']);
```

**Send Messages:**

```php
// Simple message
$conversation->sendMessage("Hello!");

// With attachments
$conversation->sendMessage("Check files", ['file.pdf', 'image.jpg']);

// Reply to message
$conversation->sendMessage("Thanks!", [], $messageId);
```

**Manage Participants:**

```php
// Add/remove members
$conversation->addMember($user);
$conversation->addMembers([$user1, $user2]);
$conversation->removeMember($user);

// Change roles
$conversation->makeAsAdmin($user);
$conversation->makeAsMember($user);
```

**Work with Messages:**

```php
// Get messages
$messages = $conversation->messages();
$message = $conversation->messageById($id);

// Message actions
$message->markAsRead();
$message->star();
$message->edit("Updated content");
$message->delete();
```

**Conversation Actions:**

```php
$conversation->pin();
$conversation->favorite();
$conversation->update(['title' => 'New Name']);
$conversation->clear();
$conversation->delete();
```

**Fetch Conversations:**

```php
$conversations = Chat::all();
$unread = Chat::unread();
$favorited = Chat::favorited();
$pinned = Chat::pinned();
$paginated = Chat::paginate();
$conversation = Chat::findById($id);
```

## Events

Available events: `MessageWasSent`, `MessageWasEdited`, `ConversationCreated`, `ConversationUpdated`, `ConversationCleared`, `ConversationLeft`, `ParticipantsJoined`, `ParticipantsLeft`

Register listeners in `EventServiceProvider`:

```php
protected $listen = [
    \kareemsliet\Chat\Events\ConversationCreated::class => [
        \App\Listeners\CreateConversation::class,
    ],
];
```

📖 **Learn more:** [Laravel Events Documentation](https://laravel.com/docs/events)

## Broadcasting

Enable real-time updates in `config/chat.php`:

```php
'broadcasting' => [
    'enabled' => true,
    'connection' => env('BROADCAST_CONNECTION', 'pusher'),
],
```

**Listen to events:**

```php
// MessageWasSent - fires when message is sent
Echo.private(`chat.conversations.${conversationId}`)
    .listen('MessageWasSent', (e) => {
        console.log(e.message);
    });

// MessageWasEdited - fires when message is edited
Echo.private(`chat.conversations.${conversationId}`)
    .listen('MessageWasEdited', (e) => {
        console.log(e.message);
    });
```

📖 **Learn more:** [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)

## Configuration

Publish config file: `php artisan vendor:publish --tag=chat-config`

Key settings in `config/chat.php`:
- UUID support for conversations & messages
- Event broadcasting configuration

## Testing

Testing suite under development.

## Contributing

Contributions welcome! Submit a Pull Request.

## License

MIT License. See [LICENSE](LICENSE) for details.

---

**Support:** [GitHub Issues](https://github.com/Kareemsliet/chat/issues) | **Email:** kareemoii37@gmail.com

