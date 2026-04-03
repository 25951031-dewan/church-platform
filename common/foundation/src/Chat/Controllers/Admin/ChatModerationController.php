<?php

namespace Common\Chat\Controllers\Admin;

use Common\Chat\Models\Conversation;
use Common\Chat\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatModerationController extends Controller
{
    /**
     * GET /api/v1/chat/admin/conversations
     * List all conversations for moderation.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $conversations = Conversation::with(['users', 'latestMessage.user'])
            ->orderBy('updated_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($conversations);
    }

    /**
     * GET /api/v1/chat/admin/conversations/{conversation}/messages
     * View all messages in a conversation (for moderation).
     */
    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $messages = $conversation->messages()
            ->withTrashed() // Include soft-deleted messages
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($messages);
    }

    /**
     * DELETE /api/v1/chat/admin/messages/{message}/force
     * Permanently delete a message.
     */
    public function forceDelete(Message $message): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $message->forceDelete();

        return response()->json(['message' => 'Message permanently deleted']);
    }

    /**
     * POST /api/v1/chat/admin/messages/{message}/restore
     * Restore a soft-deleted message.
     */
    public function restore(int $messageId): JsonResponse
    {
        $this->authorize('moderate', Conversation::class);

        $message = Message::withTrashed()->findOrFail($messageId);
        $message->restore();

        return response()->json([
            'message' => 'Message restored',
            'data' => $message->load('user'),
        ]);
    }
}
