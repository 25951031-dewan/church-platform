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
            ->with(['user', 'replyTo.user', 'reactions'])
            ->withCount('reads')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        // Transform to include reaction counts
        $messages->getCollection()->transform(function ($message) {
            $message->reaction_counts = $message->getReactionCounts();
            return $message;
        });

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

        // Validate reply_to if provided
        if ($request->reply_to_id) {
            $replyTo = Message::find($request->reply_to_id);
            if (!$replyTo || $replyTo->conversation_id !== $conversation->id) {
                return response()->json([
                    'message' => 'Invalid reply target',
                ], 422);
            }
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'type' => $request->type ?? 'text',
            'file_entry_id' => $request->file_entry_id,
            'reply_to_id' => $request->reply_to_id,
        ]);

        // Update conversation timestamp (for sorting)
        $conversation->touch();

        // Load relationships for broadcast
        $message->load(['user', 'replyTo.user']);

        // Broadcast message to all participants except sender
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => $message,
        ], 201);
    }

    /**
     * PUT /api/v1/chat/messages/{message}
     * Edit a message.
     */
    public function update(Message $message, Request $request): JsonResponse
    {
        // Only message owner can edit
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Can only edit text messages
        if ($message->type !== 'text') {
            return response()->json([
                'message' => 'Only text messages can be edited',
            ], 422);
        }

        // Check if edit window has passed (optional: 15 minutes)
        $editWindow = config('chat.edit_window_minutes', 15);
        if ($message->created_at->diffInMinutes(now()) > $editWindow) {
            return response()->json([
                'message' => "Messages can only be edited within {$editWindow} minutes",
            ], 422);
        }

        $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message->edit($request->input('body'));

        return response()->json([
            'data' => $message->fresh(['user', 'replyTo.user']),
        ]);
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
