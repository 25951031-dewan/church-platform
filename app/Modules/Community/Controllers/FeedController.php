<?php

namespace App\Modules\Community\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\CommunityPost;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $query = CommunityPost::published()
            ->with(['user:id,name,avatar', 'group:id,name']);

        if ($type = $request->query('type')) {
            $query->ofType($type);
        }

        if ($groupId = $request->query('group_id')) {
            $query->where('group_id', $groupId);
        }

        $sort = $request->query('sort', 'latest');
        if ($sort === 'trending') {
            $query->trending();
        } else {
            $query->latest();
        }

        $posts = $query->paginate($request->query('per_page', 20));

        // Hide user info on anonymous posts
        $posts->getCollection()->transform(function ($post) {
            if ($post->is_anonymous) {
                $post->setRelation('user', null);
                $post->user_id = null;
            }
            return $post;
        });

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:blessing,bible_study,verse,testimony,question,discussion',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'media.*' => 'string|url',
            'is_anonymous' => 'boolean',
            'group_id' => 'nullable|exists:groups,id',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['church_id'] = $request->user()->church_id;

        $post = CommunityPost::create($validated);

        return response()->json($post->load('user:id,name,avatar'), 201);
    }

    public function show(CommunityPost $communityPost)
    {
        $communityPost->load([
            'user:id,name,avatar',
            'group:id,name',
            'comments' => fn($q) => $q->whereNull('parent_id')
                ->with(['user:id,name,avatar', 'replies.user:id,name,avatar'])
                ->latest()
                ->limit(20),
            'likes' => fn($q) => $q->select('likeable_type', 'likeable_id', 'user_id', 'type'),
        ]);

        if ($communityPost->is_anonymous) {
            $communityPost->setRelation('user', null);
            $communityPost->user_id = null;
        }

        return response()->json($communityPost);
    }

    public function update(Request $request, CommunityPost $communityPost)
    {
        if ($communityPost->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'is_anonymous' => 'boolean',
        ]);

        $communityPost->update($validated);

        return response()->json($communityPost);
    }

    public function destroy(Request $request, CommunityPost $communityPost)
    {
        if ($communityPost->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $communityPost->delete();

        return response()->json(['message' => 'Post deleted']);
    }

    public function toggleLike(Request $request, CommunityPost $communityPost)
    {
        $validated = $request->validate([
            'type' => 'in:like,pray,amen',
        ]);

        $existing = $communityPost->likes()
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $communityPost->decrement('likes_count');
            return response()->json(['liked' => false]);
        }

        $communityPost->likes()->create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'] ?? 'like',
        ]);
        $communityPost->increment('likes_count');

        return response()->json(['liked' => true, 'type' => $validated['type'] ?? 'like']);
    }

    public function share(Request $request, CommunityPost $communityPost)
    {
        $validated = $request->validate([
            'platform' => 'required|in:internal,facebook,twitter,copy_link',
        ]);

        $communityPost->shares()->create([
            'user_id' => $request->user()->id,
            'platform' => $validated['platform'],
        ]);
        $communityPost->increment('shares_count');

        return response()->json(['shared' => true]);
    }
}
