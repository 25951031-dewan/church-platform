<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Page::query()->with('parent')->orderBy('sort_order')->latest();
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%");
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        return response()->json($query->paginate(15));
    }

    public function published(): JsonResponse
    {
        $pages = Page::published()->orderBy('sort_order')->get();
        return response()->json(['data' => $pages]);
    }

    public function show(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)->firstOrFail();
        return response()->json(['data' => $page]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:pages,id',
            'template' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,published',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $slug = Str::slug($validated['title']);
        $original = $slug;
        $counter = 1;
        while (Page::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        }

        $validated['author_id'] = $request->user()?->id;
        $page = Page::create($validated);
        return response()->json(['message' => 'Page created.', 'data' => $page], 201);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:pages,id',
            'template' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,published',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        if (isset($validated['title']) && $validated['title'] !== $page->title) {
            $slug = Str::slug($validated['title']);
            $original = $slug;
            $counter = 1;
            while (Page::where('slug', $slug)->where('id', '!=', $page->id)->exists()) {
                $slug = $original . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('featured_image')) {
            if ($page->featured_image) {
                Storage::disk('public')->delete($page->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('pages', 'public');
        }

        $page->update($validated);
        return response()->json(['message' => 'Page updated.', 'data' => $page->fresh()]);
    }

    public function destroy(Page $page): JsonResponse
    {
        if ($page->featured_image) {
            Storage::disk('public')->delete($page->featured_image);
        }
        $page->delete();
        return response()->json(['message' => 'Page deleted.']);
    }
}
