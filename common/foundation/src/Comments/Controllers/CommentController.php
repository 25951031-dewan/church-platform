<?php

namespace Common\Comments\Controllers;

use Common\Comments\Models\Comment;
use Common\Comments\Requests\ModifyComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_id' => 'required|integer',
            'commentable_type' => 'required|string|in:post,sermon,prayer_request,article',
            'page' => 'integer|min:1',
        ]);

        $comments = Comment::where([
                'commentable_id' => $validated['commentable_id'],
                'commentable_type' => $validated['commentable_type'],
            ])
            ->whereNull('parent_id')
            ->with(['user:id,name,avatar', 'replies.user:id,name,avatar', 'reactions'])
            ->withCount('replies')
            ->latest()
            ->paginate(15);

        return response()->json($comments);
    }

    public function store(ModifyComment $request): JsonResponse
    {
        Gate::authorize('create', Comment::class);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_id' => $request->input('commentable_id'),
            'commentable_type' => $request->input('commentable_type'),
            'body' => $request->input('body'),
            'parent_id' => $request->input('parent_id'),
        ]);

        $comment->load('user:id,name,avatar');

        return response()->json(['comment' => $comment], 201);
    }

    public function update(ModifyComment $request, Comment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $comment->update(['body' => $request->input('body')]);

        return response()->json(['comment' => $comment]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->noContent();
    }
}
