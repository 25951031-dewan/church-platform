<?php

namespace Plugins\Post\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Post\Models\Post;
use Plugins\Post\Services\PollVoteService;

class PollVoteController extends Controller
{
    public function __construct(private PollVoteService $service) {}

    /** POST /api/v1/posts/{id}/vote */
    public function store(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['option_id' => ['required', 'string']]);

        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'poll', 422, 'Not a poll.');

        // Check expiry
        $endsAt = $post->meta['ends_at'] ?? null;
        if ($endsAt && Carbon::parse($endsAt)->isPast()) {
            return response()->json(['message' => 'Poll has ended'], 422);
        }

        $counts = $this->service->vote($post, $request->user()->id, $data['option_id']);

        return response()->json($counts, 201);
    }

    /** DELETE /api/v1/posts/{id}/vote */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'poll', 422, 'Not a poll.');

        $this->service->removeVote($post, $request->user()->id);

        return response()->json(['message' => 'Vote removed']);
    }

    /** GET /api/v1/posts/{id}/votes */
    public function counts(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);
        $userId = $request->user()?->id;

        return response()->json($this->service->counts($post, $userId));
    }
}
