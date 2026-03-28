<?php

namespace Common\Reactions\Controllers;

use Common\Reactions\Models\Reaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReactionController extends Controller
{
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactable_id' => 'required|integer',
            'reactable_type' => 'required|string|in:post,comment,event,sermon',
            'type' => 'required|string|in:' . implode(',', Reaction::TYPES),
        ]);

        $existing = Reaction::where([
            'user_id' => $request->user()->id,
            'reactable_id' => $validated['reactable_id'],
            'reactable_type' => $validated['reactable_type'],
        ])->first();

        if ($existing) {
            if ($existing->type === $validated['type']) {
                $existing->delete();
                return response()->json(['reaction' => null, 'action' => 'removed']);
            }
            $existing->update(['type' => $validated['type']]);
            return response()->json(['reaction' => $existing->fresh(), 'action' => 'switched']);
        }

        $reaction = Reaction::create([
            'user_id' => $request->user()->id,
            'reactable_id' => $validated['reactable_id'],
            'reactable_type' => $validated['reactable_type'],
            'type' => $validated['type'],
        ]);

        return response()->json(['reaction' => $reaction, 'action' => 'created'], 201);
    }
}
