<?php
namespace Plugins\Comment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Comment\Models\Comment;
use Plugins\Post\Models\Post;

class CommentController extends Controller
{
    /** @group Comments */
    public function index(int $postId): JsonResponse
    {
        $comments = Comment::where('commentable_type', Post::class)
            ->where('commentable_id', $postId)
            ->whereNull('parent_id')
            ->with(['author:id,name,avatar', 'replies.author:id,name,avatar'])
            ->latest()->paginate(20);

        return response()->json($comments);
    }

    /**
     * @group Comments
     * @bodyParam post_id   integer required Example: 1
     * @bodyParam body      string  required Example: "Great post!"
     * @bodyParam parent_id integer optional Example: 5
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id'   => ['required', 'integer', 'exists:social_posts,id'],
            'body'      => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        $comment = DB::transaction(function () use ($validated, $request) {
            $comment = Comment::create([
                'commentable_type' => Post::class,
                'commentable_id'   => $validated['post_id'],
                'user_id'          => $request->user()->id,
                'parent_id'        => $validated['parent_id'] ?? null,
                'body'             => $validated['body'],
            ]);

            if ($comment->parent_id) {
                Comment::where('id', $comment->parent_id)->increment('replies_count');
            }
            DB::table('social_posts')->where('id', $validated['post_id'])->increment('comments_count');

            return $comment;
        });

        return response()->json($comment->load('author:id,name,avatar'), 201);
    }

    /** @group Comments */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        abort_if($comment->user_id !== $request->user()->id, 403);
        $comment->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
