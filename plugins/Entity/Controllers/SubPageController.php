<?php

namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;

class SubPageController extends Controller
{
    /** List sub-pages of a parent page (public). */
    public function index(int $id): JsonResponse
    {
        $parent = ChurchEntity::pages()->active()->findOrFail($id);

        $subPages = $parent->subPages()
            ->where('type', 'page')
            ->where('is_active', true)
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->latest()
            ->paginate(20);

        return response()->json($subPages);
    }

    /** Create a sub-page under a parent (admin of parent only). */
    public function store(Request $request, int $id): JsonResponse
    {
        $parent = ChurchEntity::pages()->active()->findOrFail($id);
        abort_unless($parent->isAdmin($request->user()->id), 403, 'Only page admins may create sub-pages.');

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $slug = $this->uniqueSlug(Str::slug($data['name']));

        $subPage = ChurchEntity::create(array_merge($data, [
            'type'             => 'page',
            'owner_id'         => $request->user()->id,
            'slug'             => $slug,
            'parent_entity_id' => $parent->id,
        ]));

        // Auto-add creator as admin member of the sub-page
        EntityMember::create([
            'entity_id' => $subPage->id,
            'user_id'   => $request->user()->id,
            'role'      => 'admin',
            'status'    => 'approved',
        ]);

        $subPage->increment('members_count');

        return response()->json($subPage->load('owner:id,name,avatar'), 201);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i    = 1;
        while (ChurchEntity::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug ?: 'page-' . uniqid();
    }
}
