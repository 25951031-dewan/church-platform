<?php

namespace Common\Chat\Controllers;

use Common\Chat\Models\Conversation;
use Common\Chat\Models\Message;
use Common\Chat\Models\MessageRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReadReceiptController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Mark messages as read up to a specific message.
     */
    public function markRead(Conversation $conversation, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $lastMessageId = $request->input('last_message_id');

        // Get all unread messages in the conversation (not sent by this user)
        $query = Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($lastMessageId) {
            $query->where('id', '<=', $lastMessageId);
        }

        $messageIds = $query->pluck('id');

        // Create read receipts
        $now = now();
        $reads = $messageIds->map(fn($id) => [
            'message_id' => $id,
            'user_id' => $user->id,
            'read_at' => $now,
        ])->toArray();

        if (!empty($reads)) {
            MessageRead::insert($reads);
        }

        // Also update the conversation pivot
        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => $now,
        ]);

        return response()->json([
            'marked_count' => count($reads),
        ]);
    }

    /**
     * Get read receipts for a message.
     */
    public function show(Message $message, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$message->conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reads = $message->reads()
            ->with('user:id,name,avatar')
            ->orderBy('read_at', 'desc')
            ->get();

        return response()->json([
            'reads' => $reads,
            'read_by_count' => $reads->count(),
        ]);
    }
}
