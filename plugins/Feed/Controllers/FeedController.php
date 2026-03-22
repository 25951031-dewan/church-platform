<?php
namespace Plugins\Feed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Post\Models\Post;

class FeedController extends Controller
{
    /**
     * Home feed — posts from communities/churches the authenticated user follows.
     * Falls back to all published posts when the user has no memberships.
     * @group Feed
     */
    public function home(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Post::published()
            ->with(['author:id,name,avatar', 'church:id,name,logo'])
            ->withCount(['comments', 'reactions'])
            ->latest('published_at');

        if ($user) {
            $communityIds = DB::table('community_members')->where('user_id', $user->id)->where('status', 'approved')->pluck('community_id');
            $churchIds    = DB::table('church_members')->where('user_id', $user->id)->pluck('church_id');

            if ($communityIds->isNotEmpty() || $churchIds->isNotEmpty()) {
                $query->where(fn ($q) => $q->whereIn('community_id', $communityIds)->orWhereIn('church_id', $churchIds));
            }
        }

        return response()->json($query->paginate(15));
    }

    /** @group Feed @urlParam communityId integer required Example: 1 */
    public function community(int $communityId): JsonResponse
    {
        return response()->json(
            Post::published()->where('community_id', $communityId)
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    /** @group Feed @urlParam churchId integer required Example: 1 */
    public function church(int $churchId): JsonResponse
    {
        return response()->json(
            Post::published()->where('church_id', $churchId)
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }
}
