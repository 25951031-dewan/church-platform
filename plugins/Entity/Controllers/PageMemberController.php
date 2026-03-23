<?php
namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Entity\Policies\ChurchEntityPolicy;

class PageMemberController extends Controller
{
    public function index(int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);

        return $page->approvedMembers()
                    ->with('user:id,name,avatar')
                    ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2 ELSE 3 END")
                    ->paginate(50);
    }

    public function updateRole(Request $request, int $id, int $userId)
    {
        $page   = ChurchEntity::pages()->findOrFail($id);
        $policy = new ChurchEntityPolicy();
        abort_unless($policy->manageMembers($request->user(), $page), 403);

        $data   = $request->validate(['role' => 'required|in:admin,moderator,member']);
        $member = EntityMember::where('entity_id', $id)
                               ->where('user_id', $userId)
                               ->where('status', 'approved')
                               ->firstOrFail();

        $member->update($data);
        return response()->json($member->load('user:id,name,avatar'));
    }

    public function destroy(Request $request, int $id, int $userId)
    {
        $page   = ChurchEntity::pages()->findOrFail($id);
        $policy = new ChurchEntityPolicy();
        abort_unless($policy->manageMembers($request->user(), $page), 403);

        // Prevent removing the last admin
        $isLastAdmin = EntityMember::where('entity_id', $id)
                                   ->where('user_id', $userId)
                                   ->where('role', 'admin')
                                   ->exists();

        if ($isLastAdmin && $page->admins()->count() <= 1) {
            return response()->json(['message' => 'Cannot remove the last admin'], 422);
        }

        EntityMember::where('entity_id', $id)->where('user_id', $userId)->delete();
        ChurchEntity::where('id', $id)->where('members_count', '>', 0)->decrement('members_count');

        return response()->json(null, 204);
    }
}
