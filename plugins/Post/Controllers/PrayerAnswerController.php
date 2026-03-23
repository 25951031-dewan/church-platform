<?php

namespace Plugins\Post\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Post\Models\Post;

class PrayerAnswerController extends Controller
{
    /** POST /api/v1/posts/{id}/answer-prayer */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $post = Post::published()->findOrFail($id);
        abort_if($post->type !== 'prayer', 422, 'Not a prayer post.');
        abort_if($post->user_id !== $request->user()->id, 403, 'Only the author can mark this as answered.');

        $meta = $post->meta ?? [];
        $answered = ! ($meta['answered'] ?? false);

        $post->update([
            'meta' => array_merge($meta, [
                'answered' => $answered,
                'answered_at' => $answered ? now()->toIso8601String() : null,
            ]),
        ]);

        return response()->json([
            'answered' => $answered,
            'answered_at' => $answered ? now()->toIso8601String() : null,
        ]);
    }
}
