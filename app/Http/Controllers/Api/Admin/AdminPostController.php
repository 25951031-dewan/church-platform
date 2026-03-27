<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Post\Models\Post;

class AdminPostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Post::with(['author:id,name'])
                ->withCount(['comments', 'reactions'])
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->when($request->search, fn ($q) => $q->where('body', 'like', "%{$request->search}%"))
                ->latest()
                ->paginate(15)
        );
    }

    public function destroy(Post $post): JsonResponse
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted']);
    }

    public function moderate(Request $request, Post $post): JsonResponse
    {
        $data = $request->validate(['status' => 'required|in:published,rejected']);
        $post->update(['status' => $data['status']]);

        return response()->json($post);
    }
}
