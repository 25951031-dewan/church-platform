<?php

namespace Common\Chat\Controllers;

use Common\Chat\Models\Message;
use Common\Chat\Models\MessageReaction;
use Common\Chat\Events\MessageReacted;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Add or toggle a reaction on a message.
     */
    public function toggle(Message $message, Request $request): JsonResponse
    {
        $request->validate([
            'emoji' => 'required|string|max:32',
        ]);

        $user = $request->user();
        $emoji = $request->input('emoji');

        // Verify user is part of the conversation
        if (!$message->conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Check if reaction exists
        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
            $action = 'removed';
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
            $action = 'added';
        }

        // Get updated reaction counts
        $reactions = $message->getReactionCounts();

        // Broadcast the reaction change
        broadcast(new MessageReacted($message, $emoji, $user, $action))->toOthers();

        return response()->json([
            'action' => $action,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Get all reactions for a message.
     */
    public function index(Message $message, Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$message->conversation->hasUser($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $reactions = $message->reactions()
            ->with('user:id,name,avatar')
            ->get()
            ->groupBy('emoji')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'users' => $group->pluck('user'),
                ];
            });

        return response()->json([
            'reactions' => $reactions,
        ]);
    }
}
