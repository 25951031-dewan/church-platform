<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        $menus = Menu::orderBy('location')->get();
        return response()->json(['data' => $menus]);
    }

    public function show(string $location): JsonResponse
    {
        $menu = Menu::where('location', $location)->where('is_active', true)->first();
        return response()->json(['data' => $menu]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|in:header,footer,sidebar',
            'items' => 'nullable|array',
            'items.*.label' => 'required|string|max:255',
            'items.*.type' => 'required|string|in:page,category,link,post',
            'items.*.target' => 'nullable|string|max:500',
            'items.*.children' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);
        $menu = Menu::create($validated);
        return response()->json(['message' => 'Menu created.', 'data' => $menu], 201);
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|in:header,footer,sidebar',
            'items' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);
        $menu->update($validated);
        return response()->json(['message' => 'Menu updated.', 'data' => $menu->fresh()]);
    }

    public function destroy(Menu $menu): JsonResponse
    {
        $menu->delete();
        return response()->json(['message' => 'Menu deleted.']);
    }
}
