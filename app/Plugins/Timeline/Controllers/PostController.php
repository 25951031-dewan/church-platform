<?php

namespace App\Plugins\Timeline\Controllers;

use App\Plugins\Timeline\Models\Post;
use App\Plugins\Timeline\Requests\ModifyPost;
use App\Plugins\Timeline\Services\CrupdatePost;
use App\Plugins\Timeline\Services\DeletePosts;
use App\Plugins\Timeline\Services\PaginatePosts;
use App\Plugins\Timeline\Services\PostLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(
        private PostLoader $loader,
        private CrupdatePost $crupdate,
        private PaginatePosts $paginator,
        private DeletePosts $deleter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $posts = $this->paginator->execute($request);
        return response()->json($posts);
    }

    public function show(Post $post): JsonResponse
    {
        Gate::authorize('view', $post);
        return response()->json(['post' => $this->loader->loadForFeed($post)]);
    }

    public function store(ModifyPost $request): JsonResponse
    {
        Gate::authorize('create', Post::class);

        if ($request->input('type') === 'announcement') {
            Gate::authorize('announce', Post::class);
        }

        $post = $this->crupdate->execute([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'post' => $this->loader->loadForFeed($post),
        ], 201);
    }

    public function update(ModifyPost $request, Post $post): JsonResponse
    {
        Gate::authorize('update', $post);

        $post = $this->crupdate->execute($request->validated(), $post);

        return response()->json([
            'post' => $this->loader->loadForFeed($post),
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        Gate::authorize('delete', $post);

        $this->deleter->execute([$post->id]);

        return response()->noContent();
    }

    public function pin(Post $post): JsonResponse
    {
        Gate::authorize('pin', Post::class);

        $post->update(['is_pinned' => !$post->is_pinned]);

        return response()->json(['is_pinned' => $post->is_pinned]);
    }

    /**
     * Get feed data for public consumption (optimized for timeline feed)
     */
    public function feedData(Request $request): JsonResponse
    {
        $request->validate([
            'church_id' => 'nullable|integer|exists:churches,id',
            'limit' => 'nullable|integer|min:5|max:50',
            'offset' => 'nullable|integer|min:0',
        ]);

        $churchId = $request->query('church_id', 1); // Default to church ID 1
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);

        // Get posts for the church (public posts only)
        $posts = Post::where('church_id', $churchId)
            ->where('is_public', true)
            ->where('status', 'published')
            ->with(['user:id,name,avatar_url', 'media', 'church:id,name'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'type' => $post->type,
                    'is_pinned' => $post->is_pinned,
                    'created_at' => $post->created_at,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'avatar_url' => $post->user->avatar_url,
                    ],
                    'church' => [
                        'id' => $post->church->id,
                        'name' => $post->church->name,
                    ],
                    'media' => $post->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'type' => $media->type,
                            'url' => $media->url,
                            'thumbnail_url' => $media->thumbnail_url,
                        ];
                    }),
                    'stats' => [
                        'likes_count' => $post->likes_count ?? 0,
                        'comments_count' => $post->comments_count ?? 0,
                        'shares_count' => $post->shares_count ?? 0,
                    ],
                ];
            });

        return response()->json([
            'posts' => $posts,
            'has_more' => $posts->count() === $limit,
            'next_offset' => $offset + $limit,
        ]);
    }
}
