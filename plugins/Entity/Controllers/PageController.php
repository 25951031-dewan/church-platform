<?php
namespace Plugins\Entity\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Entity\Models\ChurchEntity;
use Plugins\Entity\Models\EntityMember;
use Plugins\Entity\Policies\ChurchEntityPolicy;

class PageController extends Controller
{
    public function index(Request $request)
    {
        return ChurchEntity::pages()->active()
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->when($request->search,   fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->verified, fn($q) => $q->where('is_verified', true))
            ->latest()->paginate(20);
    }

    public function show(string $slug)
    {
        return ChurchEntity::pages()->active()
            ->where('slug', $slug)
            ->with('owner:id,name,avatar')
            ->withCount('approvedMembers')
            ->firstOrFail();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $slug = $this->uniqueSlug(Str::slug($data['name']));

        $page = ChurchEntity::create(array_merge($data, [
            'type'     => 'page',
            'owner_id' => $request->user()->id,
            'slug'     => $slug,
        ]));

        // Auto-add creator as admin member
        EntityMember::create([
            'entity_id' => $page->id,
            'user_id'   => $request->user()->id,
            'role'      => 'admin',
            'status'    => 'approved',
        ]);

        $page->members_count = 1;
        $page->save();

        return response()->json($page, 201);
    }

    public function update(Request $request, int $id)
    {
        $page   = ChurchEntity::pages()->findOrFail($id);
        $policy = new ChurchEntityPolicy();
        abort_unless($policy->update($request->user(), $page), 403);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'website'     => 'nullable|url',
            'address'     => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $page->update($data);
        return response()->json($page);
    }

    public function destroy(Request $request, int $id)
    {
        $page   = ChurchEntity::pages()->findOrFail($id);
        $policy = new ChurchEntityPolicy();
        abort_unless($policy->delete($request->user(), $page), 403);
        $page->delete();
        return response()->json(null, 204);
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
