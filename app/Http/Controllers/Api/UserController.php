<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['roles:id,name,slug'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email',
            'password'              => 'sometimes|string|min:8',
            'is_admin'              => 'sometimes|boolean',
            'phone'                 => 'nullable|string|max:30',
            'church_name'           => 'nullable|string|max:255',
            'social_id'             => 'nullable|string|max:255',
            'spiritual_background'  => 'nullable|string|max:2000',
            'custom_fields'         => 'nullable|array',
        ]);

        if (empty($validated['password'])) {
            $validated['password'] = Hash::make('password');
        }

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data'    => $user,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'sometimes|required|string|max:255',
            'email'                 => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'password'              => 'sometimes|string|min:8',
            'is_admin'              => 'sometimes|boolean',
            'phone'                 => 'nullable|string|max:30',
            'church_name'           => 'nullable|string|max:255',
            'social_id'             => 'nullable|string|max:255',
            'spiritual_background'  => 'nullable|string|max:2000',
            'custom_fields'         => 'nullable|array',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data'    => $user->fresh(),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
