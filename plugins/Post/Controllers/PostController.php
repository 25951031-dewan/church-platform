<?php

namespace Plugins\Post\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\Post;

class PostController extends Controller
{
    /**
     * POST /api/v1/posts
     * Create a new post, optionally cross-posting to multiple targets.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body'                => ['required_without:media', 'nullable', 'string'],
            'media'               => ['nullable', 'array'],
            'type'                => ['nullable', 'string'],
            'church_id'           => ['nullable', 'integer', 'exists:churches,id'],
            'community_id'        => ['nullable', 'integer', 'exists:communities,id'],
            'is_anonymous'        => ['boolean'],
            'cross_post_targets'  => ['nullable', 'array'],
            'cross_post_targets.*.community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'cross_post_targets.*.church_id'    => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $post = Post::create(array_merge($data, [
            'user_id'      => $request->user()->id,
            'status'       => 'published',
            'published_at' => now(),
        ]));

        // Cross-post to additional targets
        if (! empty($data['cross_post_targets'])) {
            $this->crossPost($post, $data['cross_post_targets'], $request->user()->id);
        }

        return response()->json($post->load('author', 'community', 'church'), 201);
    }

    /**
     * POST /api/v1/posts/{id}/cross-post
     * Cross-post an existing post to additional communities/churches.
     * Body: { targets: [{ community_id? }, { church_id? }] }
     */
    public function crossPost(Request|Post $requestOrPost, array $targets = [], int $userId = 0): JsonResponse|null
    {
        // Called directly as a route action
        if ($requestOrPost instanceof Request) {
            $request = $requestOrPost;
            $post    = Post::published()->findOrFail($request->route('id'));

            if ($post->user_id !== $request->user()->id) {
                abort(403, 'You can only cross-post your own posts.');
            }

            $targets = $request->validate([
                'targets'                      => ['required', 'array', 'min:1', 'max:10'],
                'targets.*.community_id'       => ['nullable', 'integer', 'exists:communities,id'],
                'targets.*.church_id'          => ['nullable', 'integer', 'exists:churches,id'],
            ])['targets'];

            $userId = $request->user()->id;
        } else {
            $post = $requestOrPost;
        }

        $created = [];

        DB::transaction(function () use ($post, $targets, $userId, &$created) {
            foreach ($targets as $target) {
                $communityId = $target['community_id'] ?? null;
                $churchId    = $target['church_id'] ?? null;

                if (! $communityId && ! $churchId) {
                    continue;
                }

                // Avoid duplicate reshares to the same target
                $alreadyShared = Post::where('parent_id', $post->id)
                    ->where('community_id', $communityId)
                    ->where('church_id', $churchId)
                    ->exists();

                if ($alreadyShared) {
                    continue;
                }

                $reshare = Post::create([
                    'user_id'      => $userId,
                    'parent_id'    => $post->id,
                    'community_id' => $communityId,
                    'church_id'    => $churchId,
                    'type'         => $post->type,
                    'body'         => null, // content comes from parent
                    'status'       => 'published',
                    'published_at' => now(),
                ]);

                $created[] = $reshare;
            }

            if (! empty($created)) {
                $post->increment('shares_count', count($created));
            }
        });

        // When called as a route action, return JSON
        if ($requestOrPost instanceof Request) {
            return response()->json(['shared_to' => count($created)]);
        }

        return null;
    }
}
