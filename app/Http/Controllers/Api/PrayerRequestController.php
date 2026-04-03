<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrayerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrayerRequestController extends Controller
{
    /**
     * List all prayer requests with pagination (admin view).
     */
    public function index(): JsonResponse
    {
        $prayerRequests = PrayerRequest::orderBy('created_at', 'desc')->paginate(15);

        return response()->json($prayerRequests);
    }

    /**
     * List approved and public prayer requests for the community.
     */
    public function publicRequests(): JsonResponse
    {
        $prayerRequests = PrayerRequest::where('status', 'approved')
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($prayerRequests);
    }

    /**
     * Store a new prayer request (public submission, no auth required).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['nullable', 'string', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'subject'     => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'is_public'   => ['sometimes', 'boolean'],
            'is_urgent'   => ['sometimes', 'boolean'],
        ]);

        // Map description to the 'request' DB column
        if (!empty($validated['description'])) {
            $validated['request'] = $validated['description'];
        }

        $validated['status']       = 'pending';
        $validated['prayer_count'] = 0;

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        $prayerRequest = PrayerRequest::create($validated);

        return response()->json([
            'message'        => 'Prayer request submitted successfully.',
            'prayer_request' => $prayerRequest,
        ], 201);
    }

    /**
     * Update a prayer request.
     */
    public function update(Request $request, PrayerRequest $prayerRequest): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'email'       => ['nullable', 'string', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'subject'     => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'is_public'   => ['sometimes', 'boolean'],
            'is_urgent'   => ['sometimes', 'boolean'],
        ]);

        $prayerRequest->update($validated);

        return response()->json([
            'message'        => 'Prayer request updated successfully.',
            'prayer_request' => $prayerRequest->fresh(),
        ]);
    }

    /**
     * Update the status of a prayer request (approve, reject, etc.).
     */
    public function updateStatus(Request $request, PrayerRequest $prayerRequest): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,answered,rejected'],
        ]);

        $prayerRequest->update($validated);

        return response()->json([
            'message'        => 'Prayer request status updated successfully.',
            'prayer_request' => $prayerRequest->fresh(),
        ]);
    }

    /**
     * Increment the prayer count for a prayer request.
     */
    public function pray(PrayerRequest $prayerRequest): JsonResponse
    {
        $prayerRequest->increment('prayer_count');

        return response()->json([
            'message'        => 'Thank you for praying.',
            'prayer_request' => $prayerRequest->fresh(),
        ]);
    }

    /**
     * Delete a prayer request.
     */
    public function destroy(PrayerRequest $prayerRequest): JsonResponse
    {
        $prayerRequest->delete();

        return response()->json([
            'message' => 'Prayer request deleted successfully.',
        ]);
    }
}
