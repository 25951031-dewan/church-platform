<?php

namespace Common\Chat\Controllers;

use Common\Chat\Events\MessageRead;
use Common\Chat\Models\Conversation;
use Common\Chat\Requests\CreateConversationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ConversationController extends Controller
{
    /**
     * GET /api/v1/chat/conversations
     * List all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = $user
            ->conversations()
            ->with(['latestMessage.user', 'users'])
            ->get()
            ->map(fn(Conversation $conv) => [
                'id' => $conv->id,
                'type' => $conv->type,
                'name' => $conv->type === 'group' ? $conv->name : null,
                'display_name' => $conv->getDisplayNameFor($user),
                'users' => $conv->users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'avatar' => $u->avatar,
                ]),
                'latest_message' => $conv->latestMessage ? [
                    'id' => $conv->latestMessage->id,
                    'body' => $conv->latestMessage->body,
                    'type' => $conv->latestMessage->type,
                    'user' => [
                        'id' => $conv->latestMessage->user->id,
                        'name' => $conv->latestMessage->user->name,
                    ],
                    'created_at' => $conv->latestMessage->created_at,
                ] : null,
                'unread_count' => $conv->unreadCountFor($user),
                'updated_at' => $conv->updated_at,
            ]);

        return response()->json(['data' => $conversations]);
    }

    /**
     * GET /api/v1/chat/conversations/{conversation}
     * Show a specific conversation.
     */
    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json([
            'data' => $conversation->load('users'),
        ]);
    }

    /**
     * POST /api/v1/chat/conversations
     * Create a new conversation (direct or group).
     */
    public function store(CreateConversationRequest $request): JsonResponse
    {
        $this->authorize('create', Conversation::class);

        $userIds = $request->user_ids;
        $type = count($userIds) > 1 ? 'group' : 'direct';

        // For groups, check group creation permission
        if ($type === 'group') {
            $this->authorize('createGroup', Conversation::class);
        }

        // For direct conversations, check if one already exists between these users
        if ($type === 'direct') {
            $existing = Conversation::where('type', 'direct')
                ->whereHas('users', fn($q) => $q->where('user_id', $request->user()->id))
                ->whereHas('users', fn($q) => $q->where('user_id', $userIds[0]))
                ->first();

            if ($existing) {
                return response()->json([
                    'data' => $existing->load('users'),
                    'message' => 'Conversation already exists',
                ]);
            }
        }

        $conversation = Conversation::create([
            'type' => $type,
            'name' => $request->name,
            'created_by' => $request->user()->id,
        ]);

        // Attach all participants (including the creator)
        $participants = array_unique([...$userIds, $request->user()->id]);
        $conversation->users()->attach($participants);

        return response()->json([
            'data' => $conversation->load('users'),
        ], 201);
    }

    /**
     * POST /api/v1/chat/conversations/{conversation}/read
     * Mark conversation as read.
     */
    public function markAsRead(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->users()
            ->updateExistingPivot($request->user()->id, [
                'last_read_at' => now(),
            ]);

        // Broadcast read receipt
        broadcast(new MessageRead(
            $conversation->id,
            $request->user()->id,
            now()->toISOString()
        ))->toOthers();

        return response()->json(['message' => 'Marked as read']);
    }

    /**
     * DELETE /api/v1/chat/conversations/{conversation}
     * Leave a conversation.
     */
    public function destroy(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('delete', $conversation);

        // Remove user from conversation (leave)
        $conversation->users()->detach($request->user()->id);

        // If no users left, delete the conversation
        if ($conversation->users()->count() === 0) {
            $conversation->delete();
        }

        return response()->json(['message' => 'Left conversation']);
    }
}
