# Chat Plugin

Real-time messaging system for the church community platform with 1-on-1 and group conversations, typing indicators, read receipts, and admin moderation.

## Features

- **1-on-1 Conversations**: Direct messaging between two users
- **Group Chats**: Multi-user conversations with custom names
- **Real-Time Messaging**: Instant message delivery via WebSocket
- **Typing Indicators**: See when others are typing
- **Read Receipts**: Know when messages are read
- **Presence Status**: Online/offline/away status tracking
- **Media Support**: Images, files, and audio messages
- **Moderation**: Admin tools to monitor and moderate chats

## Architecture

### Broadcasting

The chat system uses Laravel Broadcasting with an abstraction layer that supports:

- **Pusher** (recommended for shared hosting) - Zero server setup, free tier available
- **Reverb** (recommended for VPS) - Self-hosted, no monthly fees

Both drivers work with identical code - just change `BROADCAST_DRIVER` in `.env`.

### Database Tables

- `conversations` - Stores chat metadata (type, name, creator)
- `messages` - Stores message content with soft deletes
- `conversation_user` - Pivot table with read receipts and mute settings

## API Endpoints

### Conversations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/chat/conversations` | List user's conversations |
| POST | `/api/v1/chat/conversations` | Create new conversation |
| GET | `/api/v1/chat/conversations/{id}` | Get conversation details |
| POST | `/api/v1/chat/conversations/{id}/read` | Mark as read |
| DELETE | `/api/v1/chat/conversations/{id}` | Leave conversation |

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/chat/conversations/{id}/messages` | Get paginated messages |
| POST | `/api/v1/chat/conversations/{id}/messages` | Send message |
| DELETE | `/api/v1/chat/messages/{id}` | Delete message (soft) |
| POST | `/api/v1/chat/conversations/{id}/typing` | Send typing indicator |

### Presence

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/chat/presence` | Update presence status |

### Admin Moderation

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/chat/admin/conversations` | List all conversations |
| GET | `/api/v1/chat/admin/conversations/{id}/messages` | View all messages |
| DELETE | `/api/v1/chat/admin/messages/{id}/force` | Permanently delete |
| POST | `/api/v1/chat/admin/messages/{id}/restore` | Restore deleted |

## Permissions

| Permission | Description |
|------------|-------------|
| `chat.send` | Send messages |
| `chat.create_group` | Create group chats |
| `chat.attach_files` | Send file attachments |
| `chat.moderate` | Moderate any chat (admin) |

## Setup

### 1. Shared Hosting (Pusher)

1. Create a free account at [pusher.com](https://pusher.com)
2. Create a new Channels app
3. Add credentials to `.env`:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

4. Run `npm run build`

### 2. VPS (Reverb)

1. Install Reverb:
```bash
composer require laravel/reverb
php artisan reverb:install
```

2. Update `.env`:
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
```

3. Start Reverb server:
```bash
php artisan reverb:start
```

4. Configure Supervisor to keep Reverb running
5. Configure nginx to proxy WebSocket connections

## Frontend Usage

### Basic Chat

```tsx
import { ChatPage } from '@/plugins/chat/pages/ChatPage';

// Add route
<Route path="/chat" element={<ChatPage />} />
```

### Using Hooks

```tsx
import { useConversations, useMessages, useSendMessage } from '@/plugins/chat/hooks';

function MyComponent() {
  const { data: conversations } = useConversations();
  const { data: messages } = useMessages(conversationId);
  const sendMessage = useSendMessage();

  const handleSend = () => {
    sendMessage.mutate({
      conversation_id: conversationId,
      body: 'Hello!',
      type: 'text',
    });
  };
}
```

### Real-Time Updates

Messages are automatically updated in real-time via Laravel Echo. The `useMessages` hook subscribes to the conversation channel and updates the local cache when new messages arrive.

## File Structure

```
common/foundation/src/Chat/
├── Controllers/
│   ├── ConversationController.php
│   ├── MessageController.php
│   ├── ChatPresenceController.php
│   └── Admin/ChatModerationController.php
├── Events/
│   ├── MessageSent.php
│   ├── MessageRead.php
│   ├── TypingStarted.php
│   ├── TypingStopped.php
│   └── UserPresenceChanged.php
├── Models/
│   ├── Conversation.php
│   ├── Message.php
│   └── ConversationUser.php
├── Policies/
│   └── ConversationPolicy.php
└── Requests/
    ├── CreateConversationRequest.php
    ├── SendMessageRequest.php
    └── UpdatePresenceRequest.php

resources/client/plugins/chat/
├── components/
│   ├── ConversationList.tsx
│   ├── ConversationCard.tsx
│   ├── MessageThread.tsx
│   ├── MessageBubble.tsx
│   ├── MessageComposer.tsx
│   ├── TypingIndicator.tsx
│   ├── PresenceBadge.tsx
│   └── UnreadBadge.tsx
├── hooks/
│   ├── useConversations.ts
│   ├── useMessages.ts
│   ├── useSendMessage.ts
│   ├── useCreateConversation.ts
│   ├── useMarkAsRead.ts
│   ├── useTypingIndicator.ts
│   └── usePresence.ts
├── pages/
│   └── ChatPage.tsx
├── admin/
│   ├── ChatModerationPage.tsx
│   └── queries.ts
└── types.ts
```

## Testing

Run chat tests:

```bash
php artisan test --filter Chat
```

## Security Considerations

- All chat routes require authentication (`auth:sanctum`)
- Users can only access conversations they're participants in
- Channel authorization verifies user membership
- Admin moderation requires `chat.moderate` permission
- Messages are soft-deleted by default (can be restored)
