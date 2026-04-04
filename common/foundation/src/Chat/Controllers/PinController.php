<?php

namespace Common\Chat\Controllers;

use Common\Chat\Models\Conversation;
use Common\Chat\Models\Message;
use Common\Chat\Models\PinnedMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Get pinned messages in a conversation.
     */
    public function index(Conversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $pinned = PinnedMessage::with(['message.user', 'pinnedByUser'])
            ->where('conversation_id', $conversation->id)
            ->orderBy('pinned_at', 'desc')
            ->get();

        return response()->json([
            'pinned_messages' => $pinned,
        ]);
    }

    /**
     * Pin a message.
     */
    public function store(Message $message, Request $request): JsonResponse
    {
        $user = $request->user();
        $conversation = $message->conversation;

        if (!$conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Check if already pinned
        $existing = PinnedMessage::where('conversation_id', $conversation->id)
            ->where('message_id', $message->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Message already pinned',
            ], 422);
        }

        // Limit pins per conversation (optional)
        $pinCount = PinnedMessage::where('conversation_id', $conversation->id)->count();
        if ($pinCount >= 50) {
            return response()->json([
                'message' => 'Maximum pinned messages reached (50)',
            ], 422);
        }

        $pin = PinnedMessage::create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'pinned_by' => $user->id,
        ]);

        return response()->json([
            'pinned_message' => $pin->load(['message.user', 'pinnedByUser']),
        ], 201);
    }

    /**
     * Unpin a message.
     */
    public function destroy(Message $message, Request $request): JsonResponse
    {
        $user = $request->user();
        $conversation = $message->conversation;

        if (!$conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $deleted = PinnedMessage::where('conversation_id', $conversation->id)
            ->where('message_id', $message->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Message was not pinned',
            ], 404);
        }

        return response()->json(null, 204);
    }
}
