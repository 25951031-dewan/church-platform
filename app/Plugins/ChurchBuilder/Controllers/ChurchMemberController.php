<?php

namespace App\Plugins\ChurchBuilder\Controllers;

use App\Plugins\ChurchBuilder\Models\Church;
use App\Plugins\ChurchBuilder\Services\ChurchMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ChurchMemberController extends Controller
{
    public function __construct(
        private ChurchMemberService $memberService,
    ) {}

    public function join(Church $church): JsonResponse
    {
        $membership = $this->memberService->join($church, auth()->id());

        return response()->json([
            'membership' => $membership,
            'message' => 'Joined church successfully.',
        ], 201);
    }

    public function leave(Church $church): JsonResponse
    {
        $this->memberService->leave($church, auth()->id());

        return response()->json(null, 204);
    }

    public function members(Request $request, Church $church): JsonResponse
    {
        $query = $church->approvedMembers()
            ->with('user:id,name,avatar,email')
            ->latest('joined_at');

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        $members = $query->paginate(min((int) $request->input('per_page', 20), 50));

        return response()->json($members);
    }

    public function removeMember(Church $church, int $userId): JsonResponse
    {
        Gate::authorize('manageMembers', $church);
        $this->memberService->removeMember($church, $userId);

        return response()->json(null, 204);
    }

    public function updateRole(Request $request, Church $church, int $userId): JsonResponse
    {
        Gate::authorize('manageMembers', $church);

        $request->validate(['role' => 'required|string|in:member,admin']);

        $member = $this->memberService->updateRole($church, $userId, $request->input('role'));

        return response()->json(['member' => $member]);
    }
}
