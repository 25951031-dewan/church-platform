<?php
namespace Plugins\Feed\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Entity\Models\ChurchEntity;
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
            $churchIds    = DB::table('church_members')->where('user_id', $user->id)->where('type', 'member')->pluck('church_id');
            $entityIds    = DB::table('entity_members')->where('user_id', $user->id)->where('status', 'approved')->pluck('entity_id');

            if ($communityIds->isNotEmpty() || $churchIds->isNotEmpty() || $entityIds->isNotEmpty()) {
                $query->where(fn ($q) => $q
                    ->whereIn('community_id', $communityIds)
                    ->orWhereIn('church_id', $churchIds)
                    ->orWhereIn('entity_id', $entityIds)
                );
            }
        }

        $query->when($request->type, fn ($q) => $q->where('type', $request->type));

        return response()->json($query->paginate(15));
    }

    /** @group Feed @urlParam communityId integer required Example: 1 */
    public function community(Request $request, int $communityId): JsonResponse
    {
        return response()->json(
            Post::published()->where('community_id', $communityId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    /** @group Feed @urlParam churchId integer required Example: 1 */
    public function church(Request $request, int $churchId): JsonResponse
    {
        return response()->json(
            Post::published()->where('church_id', $churchId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar'])->withCount(['comments','reactions'])
                ->latest('published_at')->paginate(15)
        );
    }

    /** @group Feed @urlParam entityId integer required Example: 1 */
    public function page(Request $request, int $entityId): JsonResponse
    {
        ChurchEntity::pages()->findOrFail($entityId);

        return response()->json(
            Post::published()->where('entity_id', $entityId)
                ->when($request->type, fn ($q) => $q->where('type', $request->type))
                ->with(['author:id,name,avatar', 'entityActor:id,name,profile_image'])
                ->withCount(['comments', 'reactions'])
                ->latest('published_at')->paginate(15)
        );
    }
}
