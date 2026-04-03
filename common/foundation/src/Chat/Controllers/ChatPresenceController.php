<?php

namespace Common\Chat\Controllers;

use Common\Chat\Events\TypingStarted;
use Common\Chat\Events\TypingStopped;
use Common\Chat\Events\UserPresenceChanged;
use Common\Chat\Models\Conversation;
use Common\Chat\Requests\UpdatePresenceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChatPresenceController extends Controller
{
    /**
     * POST /api/v1/chat/presence
     * Update user presence status (online/offline/away).
     */
    public function update(UpdatePresenceRequest $request): JsonResponse
    {
        broadcast(new UserPresenceChanged(
            $request->user()->id,
            $request->status
        ))->toOthers();

        return response()->json(['status' => $request->status]);
    }

    /**
     * POST /api/v1/chat/conversations/{conversation}/typing
     * Broadcast typing indicator.
     */
    public function typing(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $isTyping = $request->boolean('is_typing', true);

        if ($isTyping) {
            broadcast(new TypingStarted(
                $conversation->id,
                $request->user()->id,
                $request->user()->name
            ))->toOthers();
        } else {
            broadcast(new TypingStopped(
                $conversation->id,
                $request->user()->id
            ))->toOthers();
        }

        return response()->json(['typing' => $isTyping]);
    }
}
