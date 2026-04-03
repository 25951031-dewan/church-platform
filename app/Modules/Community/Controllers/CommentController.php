<?php

namespace App\Modules\Community\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Comment;
use App\Modules\Community\Models\CommunityPost;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, CommunityPost $communityPost)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $comment = $communityPost->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $communityPost->increment('comments_count');

        return response()->json(
            $comment->load('user:id,name,avatar'),
            201
        );
    }

    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $comment->update($validated);

        return response()->json($comment);
    }

    public function destroy(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $commentable = $comment->commentable;
        $comment->delete();

        if ($commentable instanceof CommunityPost) {
            $commentable->decrement('comments_count');
        }

        return response()->json(['message' => 'Comment deleted']);
    }
}
