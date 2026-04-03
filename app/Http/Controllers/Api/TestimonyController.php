<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Testimony;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestimonyController extends Controller
{
    /**
     * List all testimonies with pagination (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Testimony::query()->latest();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('testimony', 'like', "%{$search}%")
                  ->orWhere('meta_keywords', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $testimonies = $query->paginate($request->get('per_page', 15));

        return response()->json($testimonies);
    }

    /**
     * List approved testimonies for public display.
     */
    public function approved(Request $request): JsonResponse
    {
        $query = Testimony::whereIn('status', ['approved', 'featured'])
            ->latest('published_at');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('testimony', 'like', "%{$search}%");
            });
        }

        $testimonies = $query->paginate($request->get('per_page', 15));

        return response()->json($testimonies);
    }

    /**
     * Display featured testimonies.
     */
    public function featured(Request $request): JsonResponse
    {
        $testimonies = Testimony::whereIn('status', ['approved', 'featured'])
            ->where('is_featured', true)
            ->latest('published_at')
            ->paginate($request->get('per_page', 10));

        return response()->json($testimonies);
    }

    /**
     * Display the specified testimony by slug (public).
     */
    public function show(string $slug): JsonResponse
    {
        $testimony = Testimony::where('slug', $slug)
            ->whereIn('status', ['approved', 'featured'])
            ->firstOrFail();

        $testimony->incrementView();

        return response()->json([
            'success' => true,
            'data'    => $testimony,
        ]);
    }

    /**
     * Store a new testimony (public submission, no auth required).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'born_again_date'  => 'nullable|date',
            'baptism_date'     => 'nullable|date',
            'testimony'        => 'required|string|min:20',
            'featured_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Auto-generate slug from name + timestamp for uniqueness
        $slug = Str::slug($validated['name'] . '-testimony');
        $originalSlug = $slug;
        $counter = 1;
        while (Testimony::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $validated['slug'] = $slug;

        // Auto-generate excerpt from testimony text
        $validated['excerpt'] = Str::limit(strip_tags($validated['testimony']), 200);

        // Default status is pending (needs admin approval)
        $validated['status'] = 'pending';

        // Auto-fill SEO fields
        $validated['meta_title'] = Str::limit($validated['name'] . '\'s Testimony', 70);
        $validated['meta_description'] = Str::limit($validated['excerpt'], 160);
        $validated['meta_keywords'] = 'testimony, born again, baptism, faith, church, ' . Str::lower($validated['name']);

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('testimonies/images', 'public');
        }

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        $testimony = Testimony::create($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Testimony submitted successfully. It will be visible after approval.',
            'testimony' => $testimony,
        ], 201);
    }

    /**
     * Update the specified testimony (admin).
     */
    public function update(Request $request, Testimony $testimony): JsonResponse
    {
        $validated = $request->validate([
            'name'             => 'sometimes|required|string|max:255',
            'born_again_date'  => 'nullable|date',
            'baptism_date'     => 'nullable|date',
            'testimony'        => 'sometimes|required|string|min:20',
            'excerpt'          => 'nullable|string|max:500',
            'featured_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'status'           => 'nullable|string|in:pending,approved,featured,rejected',
            'is_featured'      => 'nullable|boolean',
            'published_at'     => 'nullable|date',
            'meta_title'       => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords'    => 'nullable|string|max:500',
            'og_image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // Re-generate slug if name changed
        if (isset($validated['name']) && $validated['name'] !== $testimony->name) {
            $slug = Str::slug($validated['name'] . '-testimony');
            $originalSlug = $slug;
            $counter = 1;
            while (Testimony::where('slug', $slug)->where('id', '!=', $testimony->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Set published_at when first approved
        if (isset($validated['status']) && in_array($validated['status'], ['approved', 'featured']) && !$testimony->published_at) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            if ($testimony->featured_image) {
                Storage::disk('public')->delete($testimony->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')
                ->store('testimonies/images', 'public');
        }

        if ($request->hasFile('og_image')) {
            if ($testimony->og_image) {
                Storage::disk('public')->delete($testimony->og_image);
            }
            $validated['og_image'] = $request->file('og_image')
                ->store('testimonies/og-images', 'public');
        }

        $testimony->update($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Testimony updated successfully.',
            'testimony' => $testimony->fresh(),
        ]);
    }

    /**
     * Update status of a testimony (approve, feature, reject).
     */
    public function updateStatus(Request $request, Testimony $testimony): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,approved,featured,rejected',
        ]);

        // Set published_at when first approved
        if (in_array($validated['status'], ['approved', 'featured']) && !$testimony->published_at) {
            $validated['published_at'] = now();
        }

        // Auto-set is_featured flag
        $validated['is_featured'] = $validated['status'] === 'featured';

        $testimony->update($validated);

        return response()->json([
            'success'   => true,
            'message'   => 'Testimony status updated successfully.',
            'testimony' => $testimony->fresh(),
        ]);
    }

    /**
     * Delete a testimony.
     */
    public function destroy(Testimony $testimony): JsonResponse
    {
        if ($testimony->featured_image) {
            Storage::disk('public')->delete($testimony->featured_image);
        }
        if ($testimony->og_image) {
            Storage::disk('public')->delete($testimony->og_image);
        }

        $testimony->delete();

        return response()->json([
            'success' => true,
            'message' => 'Testimony deleted successfully.',
        ]);
    }
}
