<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ministry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinistryController extends Controller
{
    /**
     * Display a paginated listing of ministries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ministry::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('leader_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $ministries = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $ministries,
        ]);
    }

    /**
     * Display the specified ministry.
     */
    public function show(Ministry $ministry): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $ministry,
        ]);
    }

    /**
     * Store a newly created ministry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string|max:10000',
            'content'          => 'nullable|string',
            'category'         => 'nullable|string|max:255',
            'leader_name'      => 'nullable|string|max:255',
            'leader_email'     => 'nullable|email|max:255',
            'leader_phone'     => 'nullable|string|max:30',
            'meeting_schedule' => 'nullable|string|max:500',
            'meeting_location' => 'nullable|string|max:500',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'banner_image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_active'        => 'nullable|boolean',
            'is_featured'      => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        // Auto-generate slug from name
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Ministry::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('ministries/images', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $validated['banner_image'] = $request->file('banner_image')
                ->store('ministries/banners', 'public');
        }

        $ministry = Ministry::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ministry created successfully.',
            'data'    => $ministry,
        ], 201);
    }

    /**
     * Update the specified ministry.
     */
    public function update(Request $request, Ministry $ministry): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'description'      => 'nullable|string|max:10000',
            'content'          => 'nullable|string',
            'category'         => 'nullable|string|max:255',
            'leader_name'      => 'nullable|string|max:255',
            'leader_email'     => 'nullable|email|max:255',
            'leader_phone'     => 'nullable|string|max:30',
            'meeting_schedule' => 'nullable|string|max:500',
            'meeting_location' => 'nullable|string|max:500',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'banner_image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'is_active'        => 'nullable|boolean',
            'is_featured'      => 'nullable|boolean',
            'sort_order'       => 'nullable|integer|min:0',
        ]);

        // Re-generate slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $ministry->name) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Ministry::where('slug', $slug)->where('id', '!=', $ministry->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            if ($ministry->image) {
                Storage::disk('public')->delete($ministry->image);
            }
            $validated['image'] = $request->file('image')
                ->store('ministries/images', 'public');
        }

        if ($request->hasFile('banner_image')) {
            if ($ministry->banner_image) {
                Storage::disk('public')->delete($ministry->banner_image);
            }
            $validated['banner_image'] = $request->file('banner_image')
                ->store('ministries/banners', 'public');
        }

        $ministry->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Ministry updated successfully.',
            'data'    => $ministry->fresh(),
        ]);
    }

    /**
     * Remove the specified ministry.
     */
    public function destroy(Ministry $ministry): JsonResponse
    {
        if ($ministry->image) {
            Storage::disk('public')->delete($ministry->image);
        }

        if ($ministry->banner_image) {
            Storage::disk('public')->delete($ministry->banner_image);
        }

        $ministry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ministry deleted successfully.',
        ]);
    }
}
