# Laravel Chat Package

[![Latest Version](https://img.shields.io/packagist/v/kareemsliet/chat.svg?style=flat-square)](https://packagist.org/packages/kareemsliet/chat)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/kareemsliet/chat.svg?style=flat-square)](https://packagist.org/packages/kareemsliet/chat)

> ⚠️ **Note**: This package is currently under active development and testing. Use in production at your own risk.

A powerful and flexible chat system for Laravel applications with support for private conversations, group chats, real-time broadcasting, end-to-end encryption, and advanced message management.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Setup](#setup)
- [Basic Usage](#basic-usage)
  - [Creating Conversations](#creating-conversations)
  - [Sending Messages](#sending-messages)
  - [Managing Participants](#managing-participants-group-conversations)
  - [Working with Messages](#working-with-messages)
  - [Conversation Actions](#conversation-actions)
  - [Fetching Conversations](#fetching-conversations)
- [Advanced Usage](#advanced-usage)
  - [Query Builder Methods](#query-builder-methods)
  - [Message Filtering](#message-filtering)
  - [Working with Different Users](#working-with-different-users)
- [Real-time Broadcasting](#real-time-broadcasting)
- [Encryption & Security](#encryption--security)
- [Events](#events)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Testing](#testing)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

## Features

✨ **Private & Group Conversations** - Create one-on-one or group chats  
📨 **Message Management** - Send, edit, reply to, delete, and star messages  
🔔 **Real-time Broadcasting** - Built-in support for Laravel Broadcasting  
👥 **Participant Management** - Add/remove members, assign roles (admin, moderator, member)  
📌 **Pin & Favorite** - Pin important conversations and favorite messages  
🔍 **Advanced Filtering** - Query conversations by status, participants, dates, and more  
🆔 **UUID Support** - Optional UUID primary keys for conversations and messages  
📎 **File Attachments** - Support for message attachments  
🎯 **Read Receipts** - Track message read status  
🔐 **End-to-End Encryption** - RSA-2048 + AES-256 encryption  
⚙️ **Highly Configurable** - Extensive configuration options

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- One of the following databases:
  - MySQL 5.7+
  - PostgreSQL 10+
  - SQLite 3.8.8+

## Installation

Install the package via Composer:

```bash
composer require kareemsliet/chat
```

Publish the migration files:

```bash
php artisan vendor:publish --tag=chat-migrations
```

Run the migrations:

```bash
php artisan migrate
```

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=chat-config
```

## Setup

### 1. Add Trait to User Model

Add the `HasParticipant` trait to your User model:

```php
use kareemsliet\Chat\Traits\HasParticipant;

class User extends Authenticatable
{
    use HasParticipant;
    
    // Your existing code...
}
```

### 2. Participant Creation

Before users can chat, they need a participant record. This is automatically handled when using the package, or you can manually create participants:

```php
// The trait provides convenient methods
$participant = $user->findOrCreateParticipant();

// Check if user has a participant
if ($user->hasParticipant()) {
    // User can participate in conversations
}
```

## Basic Usage

### Creating Conversations

#### Private Conversation

```php
use kareemsliet\Chat\Facades\Chat;

// Create or get existing private conversation
$conversation = Chat::privateWith($otherUser);

// Create new private conversation
$conversation = Chat::newPrivate($otherUser);
```

#### Group Conversation

```php
// Create a group with default settings
$conversation = Chat::newGroup();

// Create a group with title
$conversation = Chat::newGroup([
    'title' => 'Project Team'
]);

// Create a group with full details
$conversation = Chat::newGroup([
    'title' => 'Project Team',
    'description' => 'Team discussion for the project',
    'image' => 'path/to/group-image.jpg'
]);
```

### Sending Messages

The `send()` method accepts three parameters:
- **First parameter** (required): Message content (string)
- **Second parameter** (optional): Array of attachments (default: empty array)
- **Third parameter** (optional): Reply to message ID (default: null)

```php
// Send a simple text message
$conversation->send("Hello, how are you?");

// Send message with attachments
// Method signature: send(content, attachments, replyTo)
$conversation->send(
    "Check these files",
    ['file1.pdf', 'image.jpg']
);

// Reply to a message (passing message ID as third parameter)
$conversation->send(
    "Thanks for the info!",
    [],              // Empty attachments array
    $messageId       // ID of message you're replying to
);

// Send message with both attachments and reply
$conversation->send(
    "Here are the updated files",
    ['updated_file.pdf'],
    $messageId
);

```

### Managing Participants (Group Conversations)

```php
// Add single participant to the group
$conversation->addParticipant($user->participant);

// Add multiple participants at once
$conversation->addParticipants([
    $user1->participant,
    $user2->participant,
    $user3->participant
]);

// Remove participants from the group (admin only)
$conversation->removeParticipant($user->participant);

// Remove multiple participants
$conversation->removeParticipants([
    $user1->participant,
    $user2->participant
]);

// Change participant role admin
$conversation->makeAsAdmin($participant);

// Get all participants (active and inactive)
$allParticipants = $conversation->participants();

// Get only active participants (haven't left)
$activeParticipants = $conversation->activeParticipants();

// Get participants who left the group
$leftParticipants = $conversation->leftParticipants();

// Get only admin participants
$admins = $conversation->adminParticipants();

// Current user leaves the conversation
$conversation->leave();

Or 

$conversation->exit()

// Check if current user has left
if ($conversation->hasLeft()) {
    // User is no longer in this conversation
    $leftDate = $conversation->leftAt();
}
```

### Working with Messages

```php
// Get all messages in the conversation
$messages = $conversation->messages();

// Get messages with limit and offset
// Signature: messages(query_callback, offset, limit)
$messages = $conversation->messages(
    null,    // No custom query
    0,       // Start from first message
    50       // Get 50 messages
);

// Get paginated messages (recommended for large conversations)
// Signature: messagesPaginated(perPage, query_callback, options)
$paginated = $conversation->messagesPaginated(20);

// Get unread messages only
$unreadMessages = $conversation->messages(function($query) {
    $query->unread();
});

// Mark all messages as read in this conversation
$conversation->markAsRead();

// Get count of unread messages
$unreadCount = $conversation->unreadCount();

// Access individual message by ID
$message = $conversation->messageById($messageId);

// Work with the message object
$message->markAsRead();           // Mark as read
$message->star();                 // Star the message
$message->unstar();               // Unstar the message
$message->delete();               // Delete message

// Check message status
if ($message->isRead()) {
    // Message has been read
}

if ($message->isStarred()) {
    // Message is starred
}

if ($message->isSender()) {
    // Current user sent this message
}
```

### Conversation Actions

```php
// Pin conversation to top of list
$conversation->pin();

// Unpin conversation
$conversation->unpin();

// Toggle pin status (pin if unpinned, unpin if pinned)
$conversation->togglePin();

// Check if conversation is pinned
if ($conversation->isPinned()) {
    echo "This conversation is pinned";
}

// Mark conversation as favorite
$conversation->favorite();

// Remove from favorites
$conversation->unfavorite();

// Toggle favorite status
$conversation->toggleFavorite();

// Check if favorited
if ($conversation->isFavorited()) {
    echo "This is a favorite conversation";
}

// Update conversation details (group conversations only)
// Method signature: update(attributes_array)
$conversation->update([
    'title' => 'New Group Name',
    'description' => 'Updated description',
    'image' => 'path/to/new/image.jpg'
]);

// Clear all messages from conversation (soft delete)
// Messages are hidden from your view but not permanently deleted
$conversation->clear();

// Delete conversation from your view
// Note: In group chats, this only removes you from the conversation
$conversation->deleteForMe();

// Get conversation details
$title = $conversation->title();              // Group title
$description = $conversation->description();  // Group description
$image = $conversation->image();              // Group image
$createdAt = $conversation->createdAt();      // Creation date
$type = $conversation->type();                // 'private' or 'group'

// Check conversation type
if ($conversation->isGroup()) {
    // This is a group conversation
}
```

### Fetching Conversations

```php
use kareemsliet\Chat\Facades\Chat;

// Get all conversations for authenticated user
$conversations = Chat::conversations();

// Get conversations with custom filters
// The callback receives a ConversationBuilder instance
$conversations = Chat::conversations(function($query) {
    $query->pinned()      // Only pinned conversations
          ->active()      // User hasn't left
          ->whereUnread() // Has unread messages
          ->orderByLatestMessage('desc'); // Most recent first
});

// Find specific conversation by ID
// Returns PrivateConversation or GroupConversation instance
$conversation = Chat::findById($conversationId);

// Get conversation as private conversation type
$privateConversation = Chat::privateById($conversationId);

// Get conversation as group conversation type
$groupConversation = Chat::groupById($conversationId);

// Get the other participant in a private conversation
if (!$conversation->isGroup()) {
    $otherUser = $conversation->otherParticipant();
}
```

## Advanced Usage

### Query Builder Methods

#### Conversation Filtering

```php
Chat::conversations(function($query) {
    // Status filters
    $query->pinned();              // Only pinned conversations
    $query->unpinned();            // Only unpinned conversations
    $query->favorited();           // Only favorited conversations
    $query->active();              // Not left
    $query->inactive();            // Left conversations
    $query->whereUnread();         // Has unread messages
    
    // Participant filters
    $query->whereParticipant($participant); // With specific participant
    
    // Role filters (group conversations)
    $query->admins();              // Where user is admin
    $query->members();             // Where user is member
    $query->ofRole('moderator');   // Specific role
    
    // Date filters
    $query->whereJoinedAfter('2024-01-01');
    $query->whereJoinedBefore('2024-12-31');
    $query->whereLeftAfter('2024-01-01');
    
    // Sorting
    $query->orderByLatestMessage('desc');
    $query->orderByPinned('desc');
    $query->orderByJoinDate('desc');
    $query->orderByCreatedAt('desc');
    
    // Eager loading
    $query->withLatestMessageRelation();
});
```

### Message Filtering

Use the query callback to filter messages:

```php
// Basic message filtering
$messages = $conversation->messages(function($query) {
    // Status filters
    $query->unread();              // Only unread messages
    $query->read();                // Only read messages
    $query->starred();             // Only starred messages
});

// Sender filtering
$myMessages = $conversation->messages(function($query) {
    $query->fromSender();          // Messages I sent
});

$othersMessages = $conversation->messages(function($query) {
    $query->fromOthers();          // Messages from other participants
});

// Date range filtering
$recentMessages = $conversation->messages(function($query) {
    $query->sentAfter('2024-01-01')     // After specific date
          ->sentBefore('2024-12-31');   // Before specific date
});

// Combined filters with sorting
$filteredMessages = $conversation->messages(function($query) {
    $query->unread()                    // Unread only
          ->fromOthers()                // From other users
          ->sentAfter(now()->subDays(7)) // Last 7 days
          ->orderBySentDate('desc');    // Newest first
});

// Using with pagination
$paginatedMessages = $conversation->messagesPaginated(
    20,  // 20 messages per page
    function($query) {
        $query->read()->orderBySentDate('desc');
    }
);
```

### Working with Different Users

```php
// Chat as specific user
$chat = Chat::forUser($user);
$conversations = $chat->conversations();

// Chat as authenticated user with custom guard
$chat = Chat::forAuth('admin');

// Chat as specific participant
$chat = Chat::for($participant);
```

## Events

The package dispatches several events you can listen to:

### Message Events

```php
// Message was sent
kareemsliet\Chat\Events\MessageWasSent

// Message was edited
kareemsliet\Chat\Events\MessageWasEdited
```

### Conversation Events

```php
// Conversation created
kareemsliet\Chat\Events\ConversationCreated

// Conversation updated
kareemsliet\Chat\Events\ConversationUpdated

// Conversation cleared
kareemsliet\Chat\Events\ConversationCleared

// User left conversation
kareemsliet\Chat\Events\ConversationLeft
```

### Participant Events

```php
// Participants joined
kareemsliet\Chat\Events\ParticipantsJoined

// Participants left
kareemsliet\Chat\Events\ParticipantsLeft
```

### Register Listeners

```php
// In EventServiceProvider
protected $listen = [
    \kareemsliet\Chat\Events\MessageWasSent::class => [
        \App\Listeners\SendNewMessageNotification::class,
    ],
];
```

## Configuration

The configuration file provides extensive customization options:

```php
// config/chat.php

return [

    /*
    |--------------------------------------------------------------------------
    | UUID Primary Keys - Conversations
    |--------------------------------------------------------------------------
    |
    | Enable UUID primary keys for conversations table.
    | Use UUIDs instead of auto-incrementing integers for primary keys.
    |
    */
    'use_uuid_conversations' => false,

    /*
    |--------------------------------------------------------------------------
    | UUID Primary Keys - Messages
    |--------------------------------------------------------------------------
    |
    | Enable UUID primary keys for messages table.
    | Use UUIDs instead of auto-incrementing integers for primary keys.
    |
    */
    'use_uuid_messages' => false,

    /*
    |--------------------------------------------------------------------------
    | Conversation Events Settings
    |--------------------------------------------------------------------------
    |
    | Events will fire for chat actions like messages sent, edited, etc.
    | \kareemsliet\Chat\Events\ConversationCleared
    | \kareemsliet\Chat\Events\ConversationUpdated
    | \kareemsliet\Chat\Events\ConversationCreated
    | \kareemsliet\Chat\Events\ConversationLeft
    | \kareemsliet\Chat\Events\ParticipantsJoined
    | \kareemsliet\Chat\Events\ParticipantsLeft
    | \kareemsliet\Chat\Events\ParticipantsRoleChanged
    |
    | To enable, set to true, and then create listeners to watch these events.
    |
    */
    'events_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Broadcasting Settings
    |--------------------------------------------------------------------------
    |
    | Events will fire for chat actions like messages sent, edited, etc.
    | \kareemsliet\Chat\Events\MessageWasSent
    | \kareemsliet\Chat\Events\MessageWasEdited
    |
    | Configure real-time broadcasting for messages events.
    | 
    | To enable broadcasting, set 'enabled' to true.
    |
    */
    'broadcasting' => [
        /*
         * Enable or disable real-time broadcasting for messages events.
         */
        'enabled' => true,

        /*
         * The broadcast connection to use.
         */
        "connection" => env('BROADCAST_CONNECTION'),

        /*
         * Queue broadcast events instead of dispatching them immediately.
         */
        'queue_connection' => env('QUEUE_CONNECTION'),
    ],
];

```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Issues**: [GitHub Issues](https://github.com/Kareemsliet/chat/issues)
- **Source**: [GitHub Repository](https://github.com/Kareemsliet/chat)
- **Email**: kareemoii37@gmail.com

## Security

If you discover any security-related issues, please email kareemoii37@gmail.com instead of using the issue tracker.

## Credits

- [Kareem Sliet](https://github.com/Kareemsliet)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

Made with ❤️ by [Kareem Sliet](https://github.com/Kareemsliet

