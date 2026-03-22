<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    /**
     * Get a public profile.
     * @group Profiles
     * @urlParam id integer required Example: 1
     */
    public function show(int $id): JsonResponse
    {
        return response()->json(
            User::select('id','name','avatar','cover_image','bio','location','website','created_at')->findOrFail($id)
        );
    }

    /**
     * Update authenticated user's profile.
     * @group Profiles
     * @bodyParam name        string optional
     * @bodyParam bio         string optional Max 500 chars.
     * @bodyParam location    string optional
     * @bodyParam website     string optional
     * @bodyParam avatar      string optional URL.
     * @bodyParam cover_image string optional URL.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100'],
            'bio'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'location'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'website'     => ['sometimes', 'nullable', 'url', 'max:255'],
            'avatar'      => ['sometimes', 'nullable', 'string', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
        ]);

        $request->user()->update($validated);
        return response()->json($request->user()->fresh());
    }
}
