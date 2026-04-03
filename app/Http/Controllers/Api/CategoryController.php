<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()->orderBy('sort_order');
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            $query->whereNull('parent_id');
        }
        $categories = $query->with('children')->paginate(50);
        return response()->json($categories);
    }

    public function all(Request $request): JsonResponse
    {
        $query = Category::query()->orderBy('sort_order');
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('tree') && $request->tree) {
            $query->whereNull('parent_id');
        }
        return response()->json(['data' => $query->with('children.children')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:post,page,sermon,book,bible-study',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $category = Category::create($validated);
        return response()->json(['message' => 'Category created.', 'data' => $category], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:post,page,sermon,book,bible-study',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $category->update($validated);
        return response()->json(['message' => 'Category updated.', 'data' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();
        return response()->json(['message' => 'Category deleted.']);
    }
}
