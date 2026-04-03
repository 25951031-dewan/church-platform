<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blessing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlessingController extends Controller
{
    /**
     * List all blessings with pagination.
     */
    public function index(): JsonResponse
    {
        $blessings = Blessing::orderBy('display_date', 'desc')->paginate(15);

        return response()->json($blessings);
    }

    /**
     * Get today's blessing.
     */
    public function today(): JsonResponse
    {
        $blessing = Blessing::where('display_date', now()->toDateString())->first();

        if (!$blessing) {
            return response()->json([
                'message' => 'No blessing found for today.',
            ], 404);
        }

        return response()->json([
            'blessing' => $blessing,
        ]);
    }

    /**
     * Store a new blessing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'display_date' => ['required', 'date', 'unique:blessings,display_date'],
            'author'  => ['nullable', 'string', 'max:255'],
        ]);

        $blessing = Blessing::create($validated);

        return response()->json([
            'message'  => 'Blessing created successfully.',
            'blessing' => $blessing,
        ], 201);
    }

    /**
     * Update an existing blessing.
     */
    public function update(Request $request, Blessing $blessing): JsonResponse
    {
        $validated = $request->validate([
            'title'   => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'display_date' => ['sometimes', 'required', 'date', 'unique:blessings,display_date,' . $blessing->id],
            'author'  => ['nullable', 'string', 'max:255'],
        ]);

        $blessing->update($validated);

        return response()->json([
            'message'  => 'Blessing updated successfully.',
            'blessing' => $blessing->fresh(),
        ]);
    }

    /**
     * Delete a blessing.
     */
    public function destroy(Blessing $blessing): JsonResponse
    {
        $blessing->delete();

        return response()->json([
            'message' => 'Blessing deleted successfully.',
        ]);
    }
}
