<?php

namespace Plugins\Community\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

class CommunityController extends Controller
{
    /** @group Communities */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $communities = Community::regularGroups()->active()
            ->with('creator:id,name,avatar')
            ->withCount('approvedMembers')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->church_id, fn ($q) => $q->where('church_id', $request->church_id))
            ->latest()->paginate(20);

        if ($user) {
            $myMemberships = CommunityMember::where('user_id', $user->id)
                ->whereIn('community_id', $communities->pluck('id'))
                ->pluck('status', 'community_id');

            $communities->getCollection()->transform(function ($c) use ($myMemberships) {
                $c->my_status = $myMemberships->get($c->id);

                return $c;
            });
        }

        return response()->json($communities);
    }

    /**
     * @group Communities
     *
     * @bodyParam name        string  required Example: "Sunday Youth"
     * @bodyParam description string  optional
     * @bodyParam privacy     string  optional public|private
     * @bodyParam church_id   integer optional
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'privacy' => ['sometimes', 'in:public,private,closed'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'community_type' => ['nullable', 'in:small_group,prayer_circle,bible_study,ministry_team,choir'],
        ]);

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);

        $isClosed = ($validated['privacy'] ?? 'public') === 'closed';
        $validated['requires_approval'] = $isClosed;
        $validated['privacy_closed'] = $isClosed ? '1' : '0';
        if ($isClosed) {
            $validated['privacy'] = 'private';
        }

        $community = Community::create(array_merge($validated, [
            'created_by' => $request->user()->id, 'status' => 'active', 'is_counsel_group' => false,
        ]));

        CommunityMember::create(['community_id' => $community->id, 'user_id' => $request->user()->id, 'role' => 'admin', 'status' => 'approved']);
        $community->increment('members_count');

        return response()->json($community->load('creator:id,name,avatar'), 201);
    }

    /** @group Communities */
    public function show(int $id): JsonResponse
    {
        return response()->json(
            Community::regularGroups()->with('creator:id,name,avatar')->withCount('approvedMembers')->findOrFail($id)
        );
    }

    /** @group Communities */
    public function join(Request $request, int $id): JsonResponse
    {
        $status = DB::transaction(function () use ($id, $request) {
            $community = Community::regularGroups()->active()->lockForUpdate()->findOrFail($id);
            abort_if($community->isFull(), 422, 'Community is full.');

            $existing = CommunityMember::where(['community_id' => $id, 'user_id' => $request->user()->id])->first();
            abort_if($existing, 422, 'Already a member.');

            $memberStatus = $community->requires_approval ? 'pending' : 'approved';
            CommunityMember::create(['community_id' => $id, 'user_id' => $request->user()->id, 'role' => 'member', 'status' => $memberStatus]);

            if ($memberStatus === 'approved') {
                $community->increment('members_count');
            }

            return $memberStatus;
        });

        return response()->json(['status' => $status], 201);
    }

    /** @group Communities */
    public function leave(Request $request, int $id): JsonResponse
    {
        $member = CommunityMember::where(['community_id' => $id, 'user_id' => $request->user()->id])->firstOrFail();
        abort_if($member->role === 'admin', 422, 'Admin cannot leave. Transfer ownership first.');
        $member->delete();
        if ($member->status === 'approved') {
            Community::where('id', $id)->decrement('members_count');
        }

        return response()->json(['message' => 'Left community.']);
    }
}
