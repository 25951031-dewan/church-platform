<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a paginated listing of all reviews (admin).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::query()->latest();

        if ($request->has('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->has('featured')) {
            $query->where('is_featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));
        }

        $reviews = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    /**
     * Display a paginated listing of approved reviews (public).
     */
    public function approved(Request $request): JsonResponse
    {
        $reviews = Review::where('is_approved', true)
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }

    /**
     * Store a newly submitted review.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'rating'  => 'required|integer|min:1|max:5',
            'title'   => 'required|string|max:255',
            'content' => 'required|string|max:5000',
        ]);

        $validated['is_approved'] = false;
        $validated['is_featured'] = false;

        $review = Review::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. It will be visible once approved.',
            'data'    => $review,
        ], 201);
    }

    /**
     * Approve a pending review.
     */
    public function approve(Review $review): JsonResponse
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully.',
            'data'    => $review->fresh(),
        ]);
    }

    /**
     * Toggle the featured status of a review.
     */
    public function toggleFeatured(Review $review): JsonResponse
    {
        $review->update(['is_featured' => !$review->is_featured]);

        return response()->json([
            'success' => true,
            'message' => $review->is_featured ? 'Review marked as featured.' : 'Review removed from featured.',
            'data'    => $review->fresh(),
        ]);
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully.',
        ]);
    }
}
