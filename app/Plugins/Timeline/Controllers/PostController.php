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
}
