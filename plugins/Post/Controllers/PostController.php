<?php

namespace Plugins\Post\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;
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
            'body' => ['required_without:media', 'nullable', 'string'],
            'media' => ['nullable', 'array'],
            'type' => ['nullable', 'string', 'in:post,prayer,blessing,poll,bible_study'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'is_anonymous' => ['boolean'],
            'entity_id' => ['nullable', 'integer', 'exists:church_entities,id'],
            'posted_as' => ['nullable', 'in:user,entity'],
            'actor_entity_id' => ['nullable', 'integer', 'exists:church_entities,id'],
            'cross_post_targets' => ['nullable', 'array'],
            'cross_post_targets.*.community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'cross_post_targets.*.church_id' => ['nullable', 'integer', 'exists:churches,id'],
            // poll meta
            'meta.question' => ['required_if:type,poll', 'string'],
            'meta.options' => ['required_if:type,poll', 'array', 'min:2', 'max:10'],
            'meta.options.*.text' => ['required_if:type,poll', 'string'],
            'meta.ends_at' => ['nullable', 'date'],
            'meta.allow_multiple' => ['boolean'],
            // bible_study meta
            'meta.scripture' => ['required_if:type,bible_study', 'string'],
            'meta.passage' => ['required_if:type,bible_study', 'string'],
            'meta.study_guide' => ['nullable', 'string'],
        ]);

        // For poll: generate stable option IDs and initialise votes_count
        if (($data['type'] ?? 'post') === 'poll') {
            $data['meta']['options'] = collect($data['meta']['options'])->map(fn ($opt) => [
                'id' => 'opt_'.Str::ulid(),
                'text' => $opt['text'],
                'votes_count' => 0,
            ])->all();
            $data['meta']['allow_multiple'] = $data['meta']['allow_multiple'] ?? false;
        }
        $data['type'] = $data['type'] ?? 'post';

        // Guard: entity admin required when posted_as=entity
        if (($data['posted_as'] ?? 'user') === 'entity') {
            $entityId = $data['actor_entity_id'] ?? $data['entity_id'] ?? null;
            abort_unless($entityId, 422, 'actor_entity_id required when posted_as=entity');
            $entity = ChurchEntity::findOrFail($entityId);
            abort_unless($entity->isAdmin($request->user()->id), 403, 'Not an admin of this page');
            $data['entity_id'] = $entityId;
            $data['actor_entity_id'] = $entityId;
        }
        $data['posted_as'] = $data['posted_as'] ?? 'user';

        if ($data['type'] === 'prayer' && empty($data['meta'])) {
            $data['meta'] = ['answered' => false, 'answered_at' => null];
        }

        // Guard: poll posts cannot use cross_post_targets
        if (($data['type'] ?? 'post') === 'poll' && ! empty($data['cross_post_targets'])) {
            abort(422, 'Poll posts cannot be reshared.');
        }

        $post = Post::create(array_merge($data, [
            'user_id' => $request->user()->id,
            'status' => 'published',
            'published_at' => now(),
        ]));

        // Cross-post to additional targets
        if (! empty($data['cross_post_targets'])) {
            $this->performCrossPost($post, $data['cross_post_targets'], $request->user()->id);
        }

        $post->load('author', 'community', 'church');
        $response = $post->toArray();
        if ($post->is_anonymous) {
            $response['author'] = null;
        }

        return response()->json($response, 201);
    }

    /**
     * POST /api/v1/posts/{id}/cross-post
     * Cross-post an existing post to additional communities/churches.
     * Body: { targets: [{ community_id? }, { church_id? }] }
     */
    public function crossPost(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);

        $targets = $request->validate([
            'targets' => ['required', 'array', 'min:1', 'max:10'],
            'targets.*.community_id' => ['nullable', 'integer', 'exists:communities,id'],
            'targets.*.church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ])['targets'];

        if ($post->type === 'poll') {
            abort(422, 'Poll posts cannot be reshared.');
        }

        $created = $this->performCrossPost($post, $targets, $request->user()->id);

        return response()->json(['shared_to' => count($created)]);
    }

    /**
     * Internal helper: create reshare posts for the given targets.
     */
    private function performCrossPost(Post $post, array $targets, int $userId): array
    {
        $created = [];

        DB::transaction(function () use ($post, $targets, $userId, &$created) {
            foreach ($targets as $target) {
                $communityId = $target['community_id'] ?? null;
                $churchId = $target['church_id'] ?? null;

                // Avoid duplicate reshares to the same target
                $alreadyShared = Post::where('parent_id', $post->id)
                    ->where('community_id', $communityId)
                    ->where('church_id', $churchId)
                    ->where('user_id', $userId)
                    ->exists();

                if ($alreadyShared) {
                    continue;
                }

                $reshare = Post::create([
                    'user_id' => $userId,
                    'parent_id' => $post->id,
                    'community_id' => $communityId,
                    'church_id' => $churchId,
                    'type' => $post->type,
                    'body' => null, // content comes from parent
                    'meta' => $post->meta,
                    'status' => 'published',
                    'published_at' => now(),
                ]);

                $created[] = $reshare;
            }

            if (! empty($created)) {
                $post->increment('shares_count', count($created));
            }
        });

        return $created;
    }
}
