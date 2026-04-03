<?php

namespace Common\Chat\Controllers;

use Common\Chat\Events\MessageSent;
use Common\Chat\Models\Conversation;
use Common\Chat\Models\Message;
use Common\Chat\Requests\SendMessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MessageController extends Controller
{
    /**
     * GET /api/v1/chat/conversations/{conversation}/messages
     * Get paginated messages for a conversation.
     */
    public function index(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json($messages);
    }

    /**
     * POST /api/v1/chat/conversations/{conversation}/messages
     * Send a message to a conversation.
     */
    public function store(Conversation $conversation, SendMessageRequest $request): JsonResponse
    {
        $this->authorize('send', $conversation);

        // Check file attachment permission
        if ($request->file_entry_id && !$request->user()->hasPermission('chat.attach_files')) {
            return response()->json([
                'message' => 'You do not have permission to attach files',
            ], 403);
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'type' => $request->type ?? 'text',
            'file_entry_id' => $request->file_entry_id,
        ]);

        // Update conversation timestamp (for sorting)
        $conversation->touch();

        // Load relationships for broadcast
        $message->load('user');

        // Broadcast message to all participants except sender
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => $message,
        ], 201);
    }

    /**
     * DELETE /api/v1/chat/messages/{message}
     * Delete (soft delete) a message.
     */
    public function destroy(Message $message, Request $request): JsonResponse
    {
        // Only message owner or moderator can delete
        if ($message->user_id !== $request->user()->id && !$request->user()->hasPermission('chat.moderate')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $message->delete(); // Soft delete

        return response()->json(['message' => 'Message deleted']);
    }
}
