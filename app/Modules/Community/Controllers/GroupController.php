<?php

namespace App\Modules\Community\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Group;
use App\Modules\Community\Models\GroupMember;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $query = Group::with(['creator:id,name,avatar'])
            ->withCount('members');

        $user = $request->user();

        if ($user->church_id) {
            $query->forChurchMembers($user->church_id);
        } else {
            $query->publicGroups();
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return response()->json(
            $query->latest()->paginate($request->query('per_page', 20))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:public,private,church_only',
            'cover_image' => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['church_id'] = $request->user()->church_id;

        $group = Group::create($validated);

        // Creator becomes admin of the group
        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $request->user()->id,
            'role' => 'admin',
        ]);

        return response()->json($group->load('creator:id,name,avatar'), 201);
    }

    public function show(Group $group)
    {
        return response()->json(
            $group->load([
                'creator:id,name,avatar',
                'members' => fn($q) => $q->select('users.id', 'name', 'avatar')->limit(20),
            ])->loadCount('members', 'posts')
        );
    }

    public function update(Request $request, Group $group)
    {
        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$member || !in_array($member->role, ['admin', 'moderator'])) {
            if (!$request->user()->is_admin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:public,private,church_only',
            'cover_image' => 'nullable|string',
        ]);

        $group->update($validated);

        return response()->json($group);
    }

    public function destroy(Request $request, Group $group)
    {
        if ($group->created_by !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Group deleted']);
    }

    public function join(Request $request, Group $group)
    {
        $userId = $request->user()->id;

        $exists = GroupMember::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Already a member'], 422);
        }

        if ($group->type === 'private') {
            return response()->json(['message' => 'This group requires an invitation'], 403);
        }

        if ($group->type === 'church_only' && $request->user()->church_id !== $group->church_id) {
            return response()->json(['message' => 'This group is restricted to church members'], 403);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $userId,
            'role' => 'member',
        ]);

        return response()->json(['message' => 'Joined group']);
    }

    public function leave(Request $request, Group $group)
    {
        GroupMember::where('group_id', $group->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Left group']);
    }

    public function members(Group $group)
    {
        $members = $group->members()
            ->select('users.id', 'name', 'avatar')
            ->paginate(50);

        return response()->json($members);
    }
}
