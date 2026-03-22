<?php
namespace Plugins\Reaction\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Reaction\Models\Reaction;

class ReactionController extends Controller
{
    private const ALLOWED = ['👍', '❤️', '🙏', '✝️', '🕊️'];

    /**
     * Toggle a reaction (creates or removes).
     * @group Reactions
     * @bodyParam reactable_type string  required "post" or "comment". Example: post
     * @bodyParam reactable_id   integer required Example: 1
     * @bodyParam emoji          string  optional Default: 👍
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactable_type' => ['required', 'in:post,comment'],
            'reactable_id'   => ['required', 'integer'],
            'emoji'          => ['sometimes', 'string', 'in:' . implode(',', self::ALLOWED)],
        ]);

        // Validate reactable_id exists in the correct table
        $table = $validated['reactable_type'] === 'post' ? 'social_posts' : 'comments';
        abort_unless(\Illuminate\Support\Facades\DB::table($table)->where('id', $validated['reactable_id'])->exists(), 422, 'Invalid reactable_id.');

        $map   = ['post' => \Plugins\Post\Models\Post::class, 'comment' => \Plugins\Comment\Models\Comment::class];
        $type  = $map[$validated['reactable_type']];
        $id    = $validated['reactable_id'];
        $table = $validated['reactable_type'] === 'post' ? 'social_posts' : 'comments';

        $existing = Reaction::where(['reactable_type' => $type, 'reactable_id' => $id, 'user_id' => $request->user()->id])->first();

        if ($existing) {
            DB::transaction(function () use ($existing, $table, $id) {
                $existing->delete();
                DB::table($table)->where('id', $id)->decrement('reactions_count');
            });
            return response()->json(['reacted' => false]);
        }

        DB::transaction(function () use ($type, $id, $request, $validated, $table) {
            Reaction::create(['reactable_type' => $type, 'reactable_id' => $id, 'user_id' => $request->user()->id, 'emoji' => $validated['emoji'] ?? '👍']);
            DB::table($table)->where('id', $id)->increment('reactions_count');
        });

        return response()->json(['reacted' => true], 201);
    }

    /** @group Reactions */
    public function summary(Request $request, string $type, int $id): JsonResponse
    {
        $map = ['post' => \Plugins\Post\Models\Post::class, 'comment' => \Plugins\Comment\Models\Comment::class];
        abort_unless(isset($map[$type]), 422, 'Invalid type.');

        $counts = Reaction::where(['reactable_type' => $map[$type], 'reactable_id' => $id])
            ->selectRaw('emoji, count(*) as count')->groupBy('emoji')->pluck('count', 'emoji');

        $userReaction = $request->user()
            ? Reaction::where(['reactable_type' => $map[$type], 'reactable_id' => $id, 'user_id' => $request->user()->id])->value('emoji')
            : null;

        return response()->json(['counts' => $counts, 'user_reaction' => $userReaction]);
    }
}
