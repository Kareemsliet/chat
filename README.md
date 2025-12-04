# Laravel Chat Package

A powerful and flexible chat system for Laravel applications with support for private conversations, group chats, real-time broadcasting, and advanced message management.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x


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
}
```

### 2. Participant Creation

Before users can chat, they need a participant record. This is automatically handled when using the package:

```php

$participant = $user->findOrCreateParticipant();

// Check if user has a participant
if ($user->hasParticipant()) {
    $user->createParticipant();
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

The `sendMessage()` method accepts three parameters:
- **Parameter 1** (required): Message content (string)
- **Parameter 2** (optional): Array of attachments (default: empty array)
- **Parameter 3** (optional): Reply to message - can be Message object, message ID, or null (default: null)

```php
// Send a simple text message
$conversation->sendMessage("Hello, how are you?");

// Send message with attachments
// Method signature: sendMessage(content, attachments, reply)
$conversation->sendMessage(
    "Check these files",
    ['file1.pdf', 'image.jpg']
);

// Reply to a message using message ID (as third parameter)
$conversation->sendMessage(
    "Thanks for the info!",
    [],              // Empty attachments array
    $messageId       // Message ID you're replying to
);

// Reply to a message using Message object
$originalMessage = $conversation->messageById($messageId);
$conversation->sendMessage(
    "Thanks for the info!",
    [],
    $originalMessage->getMessage()  // Message object
);

// Send message with both attachments and reply
$conversation->sendMessage(
    "Here are the updated files",
    ['updated_file.pdf', 'document.doc'],
    $messageId
);

```

### Managing Participants (Group Conversations)

The participant management methods accept flexible parameter types:
- Single Participant object
- Single User model (with HasParticipant trait)
- Array of Participants/Users
- Collection of Participants/Users
- Participant ID (integer/string)
- Multiple arguments using spread operator

```php
// ============================================
// ADDING PARTICIPANTS
// ============================================

// Add single participant using Participant object
$conversation->addMember($user->participant);

// Add single participant using User model directly
$conversation->addMember($user);

// Add multiple participants using spread operator
$conversation->addMember(
    $user1->participant,
    $user2->participant,
    $user3->participant
);

// Add multiple participants using array
$conversation->addMembers([
    $user1->participant,
]);

// Add multiple users directly (without calling participant)
$conversation->addMembers([
    $user1,  // User model
    $user2,  // User model
    $user3   // User model
]);

// Add using participant IDs
$conversation->addMembers([1, 2, 3]);

// Add using Collection
$users = User::with("participant")->whereIn('id', [1, 2, 3])->get();
$conversation->addMembers($users);

// ============================================
// REMOVING PARTICIPANTS
// ============================================

// Remove single participant
$conversation->removeMember($user->participant);

// Remove single user directly
$conversation->removeMember($user);

// Remove multiple participants using spread operator
$conversation->removeMember(
    $user1->participant,
    $user2->participant
);

// Remove multiple participants using array
$conversation->removeMembers([
    $user1->participant,
    $user2->participant
]);

// Remove using user models
$conversation->removeMembers([$user1, $user2]);

// ============================================
// CHANGING ROLES
// ============================================

// Promote single participant to admin
$conversation->makeAsAdmin($user->participant);

// Promote multiple participants to admin (accepts same flexible types)
$conversation->makeAsAdmin([
    $user1->participant,
    $user2->participant
]);

// Promote using User models directly
$conversation->makeAsAdmin([$user1, $user2]);

// Demote to member role
$conversation->makeAsMember($user->participant);

// Demote multiple participants
$conversation->makeAsMember([$user1, $user2]);

// ============================================
// GETTING PARTICIPANTS
// ============================================

// Get all participants (active and inactive)
$allParticipants = $conversation->participants();

// Get only active participants (haven't left the group)
$activeParticipants = $conversation->activeParticipants();

// Get participants who left the group
$leftParticipants = $conversation->leftParticipants();

// Get only admin participants
$admins = $conversation->adminParticipants();

// Get participants with custom query
$filteredParticipants = $conversation->participants(function($query) {
    $query->where('created_at', '>', now()->subDays(7));
});

// ============================================
// CURRENT USER ACTIONS
// ============================================

// Current user leaves the conversation
$conversation->leave();
// OR
$conversation->exit();

// Check if current user has left
if ($conversation->hasLeft()) {
    // User is no longer in this conversation
    $leftDate = $conversation->leftAt();  // Returns DateTime object
}

// Check current user's role
$role = $conversation->role();  // Returns: 'admin', 'member', null (if left conversation)

if ($conversation->isAdmin()) {
    // Current user is an admin
}

if ($conversation->isMember()) {
    // Current user is a regular member
}

// Promote current user to admin (if authorized)
$conversation->promoteToAdmin();

// Demote current user to member
$conversation->demoteToMember();
```

#### Participant Management - Real World Examples

```php
// Example 1: Add team members to a project group
$projectUsers = User::where('team_id', $teamId)->get();
$conversation->addMembers($projectUsers);

// Example 2: Remove inactive users from group by with()
$inactiveUsers = User::with("participant")->where('last_active_at', '<', now()->subDays(30))->get();
$conversation->removeMembers($inactiveUsers);

// Example 3: Promote all moderators to admin
$members = $conversation->participants(function($query) {
    $query->members();
});

$participantIds = $members->pluck('id')->toArray();
$conversation->makeAsAdmin($participantIds);

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

// Access individual message by ID (returns MessageService)
$message = $conversation->messageById($messageId);

// Or get message from Message model
$messageModel = Message::find($id);
$message = $conversation->message($messageModel);

// ============================================
// MESSAGE ACTIONS
// ============================================

// Mark as read (only for received messages, not sender's own messages)
$message->markAsRead();

// Star the message
$message->star();

// Unstar the message
$message->unstar();

// Toggle star status
$message->toggleStar();

// Edit message content
$message->edit("Updated message content");

// Edit message within time limit (default 1 day)
$message->editIn("Updated content", 'hours', 2);  // Within 2 hours

// Delete message for current user only
$message->deleteForMe();

// Delete message for everyone (permanent)
$message->delete();

// Delete within time limit
$message->deleteIn('minutes', 15);  // Within 15 minutes
$message->deleteForMeIn('hours', 24);  // Within 24 hours

// ============================================
// MESSAGE INFO
// ============================================

// Check message status
if ($message->isSender()) {
    // Current user sent this message
}

if ($message->isStarred()) {
    // Message is starred
}

// Get message details
$messageModel = $message->getMessage();     // Returns Message model
$sender = $message->getSender();            // Returns Participant
$conversation = $message->getConversation(); // Returns Conversation model
$createdAt = $message->createdAt();         // Returns DateTime
$updatedAt = $message->updatedAt();         // Returns DateTime
$starredAt = $message->starredAt();         // Returns DateTime or null
$deliveredAt = $message->deliveredAt();     // Returns DateTime or null

// Get message age
$ageInDays = $message->getAgeIn('day');
$ageInHours = $message->getAgeIn('hour');
$ageInMinutes = $message->getAgeIn('minute');
// Supported units: 'year', 'month', 'week', 'day', 'hour', 'minute', 'second'

// ============================================
// REPLY HANDLING
// ============================================

// Check if message has a reply
if ($message->hasReply()) {
    $replyMessage = $message->replyMessage();  // Returns MessageService
    $replyContent = $replyMessage->getMessage()->content;
}

// Or get reply message model directly
$replyMessageModel = $message->getReplyMessage();  // Returns Message model or null

// ============================================
// MESSAGE PARTICIPANTS
// ============================================

// Get all participants who received this message
$participants = $message->participants();

// Get participants who read this message
$readBy = $message->readBy();

foreach ($readBy as $participant) {
    echo $participant->participantable->name . " read this message\n";
}

// ============================================
// CLONE MESSAGE INSTANCE
// ============================================

// Clone the message instance
$clonedMessage = $message->clone();
```

### Conversation Actions

```php
// ============================================
// PIN / FAVORITE ACTIONS
// ============================================

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

// ============================================
// UPDATE CONVERSATION
// ============================================

// Update conversation details (works for both private and group)
// Method signature: update(attributes_array)
$conversation->update([
    'title' => 'New Group Name',
    'description' => 'Updated description',
    'image' => 'path/to/new/image.jpg'
]);

// ============================================
// GET CONVERSATION INFO
// ============================================

// Get conversation details
$conversationModel = $conversation->getConversation();  // Returns Conversation model
$createdAt = $conversation->createdAt();      // Returns DateTime object
$joinedAt = $conversation->joinedAt();        // Returns DateTime object or null
$type = $conversation->type();                // Returns 'private' or 'group'

// Check conversation type
if ($conversation->isGroup()) {
    // This is a group conversation
}

// ============================================
// CLEAR & DELETE
// ============================================

// Clear all messages from conversation (soft delete)
// Messages are hidden from your view but not permanently deleted
$conversation->clear();

// Delete conversation from your view only
// In group chats, this removes you from the conversation
$conversation->deleteForMe();

// Permanently delete conversation (admin only, deletes for everyone)
$conversation->delete();

// ============================================
// CLONE CONVERSATION INSTANCE
// ============================================

// Clone the conversation instance
$clonedConversation = $conversation->clone();
```

### Fetching Conversations

```php
use kareemsliet\Chat\Facades\Chat;

// ============================================
// GET ALL CONVERSATIONS
// ============================================

// Get all conversations with default sorting (pinned > joinDate > latestMessage)
$conversations = Chat::all();

// Get all conversations without default sorting
$conversations = Chat::all(null, false);

// Get all conversations with custom query and default sorting
$conversations = Chat::all(function($query) {
    $query->active()      // User hasn't left
          ->whereUnread(); // Has unread messages
});

// ============================================
// SPECIALIZED CONVERSATION GETTERS
// ============================================

// Get unread conversations only
$unread = Chat::unread();

// Get unread with custom query
$unread = Chat::unread(function($query) {
    $query->where('created_at', '>', now()->subDays(7));
});

// Get favorited conversations only
$favorited = Chat::favorited();

// Get pinned conversations only
$pinned = Chat::pinned();

// Get conversations with a specific participant
$conversations = Chat::withParticipant($otherUser);

// With custom query and sorting
$conversations = Chat::withParticipant($otherUser, function($query) {
    $query->active();
}, true); // true = use default sorting

// ============================================
// PAGINATION
// ============================================

// Paginate conversations (default 15 per page)
$paginated = Chat::paginate();

// Custom pagination with query
$paginated = Chat::paginate(
    20,  // Per page
    function($query) {
        $query->active()->whereUnread();
    },
    true,  // Use default sorting
    ['page_name' => 'page', 'page' => 1]  // Pagination options
);

// ============================================
// FIND SPECIFIC CONVERSATIONS
// ============================================

// Find conversation by ID (returns PrivateConversation or GroupConversation)
$conversation = Chat::findById($conversationId);

// Get as PrivateConversation instance
$privateConversation = Chat::privateById($conversationId);

// Get as GroupConversation instance
$groupConversation = Chat::groupById($conversationId);

// Get with Conversation model (manual instantiation)
$conversationModel = Conversation::find($id);
$conversation = Chat::find($conversationModel);
$privateConv = Chat::private($conversationModel);
$groupConv = Chat::group($conversationModel);
```

### Chat Facade - Message Methods

```php
use kareemsliet\Chat\Facades\Chat;

// ============================================
// WORKING WITH MESSAGES
// ============================================

// Get all messages for current participant
$messages = Chat::messages();

// Get messages with custom query
$messages = Chat::messages(function($query) {
    $query->starred()
});

// Get starred messages only
$starredMessages = Chat::starredMessages();

// Get starred messages with custom query
$starredMessages = Chat::starredMessages(function($query) {
    $query->sentAfter(now()->subMonth());
});

// Get specific message by ID
$message = Chat::messageById($messageId);

// Get message with Message model
$messageModel = Message::find($id);
$message = Chat::message($messageModel);

// ============================================
// CUSTOM MESSAGE QUERY
// ============================================

// Use messagesQuery() for complete control
$messages = Chat::messagesQuery()->unread()->get();
```

### Chat Facade - Participant Context

```php
// Get current participant
$participant = Chat::participant();

// Work with different user contexts
$userChat = Chat::forUser($user);
$authChat = Chat::forAuth('admin'); // change default guard
$participantChat = Chat::for($participant);
```

## Advanced Usage

### Query Builder Methods

#### Conversation Filtering

```php
// Use Chat::query() to build custom conversation queries
Chat::query(function($query) {
    // Status filters
    $query->pinned();              // Only pinned conversations
    $query->unpinned();            // Only unpinned conversations
    $query->favorited();           // Only favorited conversations
    $query->active();              // Not left
    $query->inactive();            // Left conversations
    
    // Participant filters
    $query->whereParticipant($participant); // With specific participant
    
    // Role filters (group conversations)
    $query->admins();              // Where user is admin
    $query->members();             // Where user is member
    
    // Date filters
    $query->whereJoinedAfter('2024-01-01');
    $query->whereJoinedBefore('2024-12-31');
    $query->whereLeftAfter('2024-01-01');
    
    // Sorting
    $query->orderByLatestMessage('desc');
    
    // Eager loading latest message
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


// Date range filtering
$recentMessages = $conversation->messages(function($query) {
    $query->sentAfter('2024-01-01')     // After specific date
});

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

## Testing

> 🚧 **Testing Framework Coming Soon**

We are committed to delivering a robust and reliable chat package. A comprehensive testing suite is currently under active development to ensure the highest quality standards.

Thank you for your patience as we build a solid testing foundation! 🙏

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- **Issues**: [GitHub Issues](https://github.com/Kareemsliet/chat/issues)
- **Source**: [GitHub Repository](https://github.com/Kareemsliet/chat)
- **Email**: kareemoii37@gmail.com

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

Made with ❤️ by [Kareem Sliet](https://github.com/Kareemsliet

