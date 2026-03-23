<?php

namespace Plugins\Community\Controllers;

use App\Services\PlatformModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Plugins\Community\Models\Community;
use Plugins\Community\Models\CommunityMember;

class CounselGroupController extends Controller
{
    public function __construct(private readonly PlatformModeService $platform) {}

    /**
     * GET /api/v1/counsel-groups
     */
    public function index(Request $request): JsonResponse
    {
        $query = Community::counselGroups()->active();

        $this->platform->scopeForMode($query);

        $groups = $query
            ->withCount('approvedMembers')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20));

        return response()->json($groups);
    }

    /**
     * POST /api/v1/counsel-groups
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'requires_approval' => ['boolean'],
            'counsellor_ids' => ['nullable', 'array'],
            'counsellor_ids.*' => ['integer', 'exists:users,id'],
            'max_members' => ['nullable', 'integer', 'min:2'],
            'is_anonymous_posting' => ['boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']).'-'.Str::random(6);
        $data['is_counsel_group'] = true;
        $data['privacy'] = 'private';
        $data['created_by'] = $request->user()?->id;

        $group = Community::create($data);

        // Auto-add creator as admin member
        if ($request->user()) {
            CommunityMember::create([
                'community_id' => $group->id,
                'user_id' => $request->user()->id,
                'role' => 'admin',
                'status' => 'approved',
            ]);
        }

        return response()->json($group, 201);
    }

    /**
     * POST /api/v1/counsel-groups/{id}/request-join
     */
    public function requestJoin(Request $request, Community $counselGroup): JsonResponse
    {
        $this->abortUnlessCounselGroup($counselGroup);

        if ($counselGroup->isFull()) {
            return response()->json(['message' => 'This group is full.'], 422);
        }

        $existing = CommunityMember::where([
            'community_id' => $counselGroup->id,
            'user_id' => $request->user()->id,
        ])->first();

        if ($existing) {
            return response()->json(['message' => 'Already a member or request pending.'], 422);
        }

        $member = CommunityMember::create([
            'community_id' => $counselGroup->id,
            'user_id' => $request->user()->id,
            'role' => 'member',
            'status' => $counselGroup->requires_approval ? 'pending' : 'approved',
        ]);

        if ($member->isApproved()) {
            $counselGroup->increment('members_count');
        }

        return response()->json($member, 201);
    }

    /**
     * POST /api/v1/counsel-groups/{id}/approve/{user}
     */
    public function approveUser(Request $request, Community $counselGroup, int $userId): JsonResponse
    {
        $this->abortUnlessCounselGroup($counselGroup);

        $actingUser = $request->user();

        if (! $counselGroup->isCounsellor($actingUser->id)) {
            abort(403, 'Only counsellors can approve members.');
        }

        $membership = CommunityMember::where([
            'community_id' => $counselGroup->id,
            'user_id' => $userId,
            'status' => 'pending',
        ])->firstOrFail();

        $membership->update(['status' => 'approved']);
        $counselGroup->increment('members_count');

        return response()->json($membership);
    }

    private function abortUnlessCounselGroup(Community $group): void
    {
        if (! $group->is_counsel_group) {
            abort(404);
        }
    }
}
