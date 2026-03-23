<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class PageFollowController extends Controller
{
    public function store(Request $request, int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);

        $existing = EntityMember::where('entity_id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already following'], 409);
        }

        DB::transaction(function () use ($page, $request) {
            EntityMember::create([
                'entity_id' => $page->id,
                'user_id' => $request->user()->id,
                'role' => 'member',
                'status' => 'approved',
            ]);
            ChurchEntity::where('id', $page->id)->increment('members_count');
        });

        return response()->json(['following' => true], 201);
    }

    public function destroy(Request $request, int $id)
    {
        $page = ChurchEntity::pages()->findOrFail($id);

        $member = EntityMember::where('entity_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('role', 'member')
            ->first();

        if (! $member) {
            return response()->json(['message' => 'Not following'], 404);
        }

        DB::transaction(function () use ($page, $member) {
            $member->delete();
            ChurchEntity::where('id', $page->id)
                ->where('members_count', '>', 0)
                ->decrement('members_count');
        });

        return response()->json(['following' => false]);
    }
}
