<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->input('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        // Status filter
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $users = $query->with('roles:id,name')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'pagination' => $users,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['roles:id,name', 'permissions:id,name']);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::defaults()],
            'roles' => 'array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'user' => $user->load('roles'),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$user->id}",
            'password' => ['sometimes', Password::defaults()],
            'is_active' => 'sometimes|boolean',
            'roles' => 'sometimes|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update(collect($validated)->except('roles')->toArray());

        if (isset($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        return response()->json([
            'user' => $user->fresh('roles'),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete your own account',
            ], 422);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    public function toggleActive(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'user' => $user,
            'message' => $user->is_active ? 'User activated' : 'User deactivated',
        ]);
    }

    public function impersonate(User $user): JsonResponse
    {
        // Store original user
        session(['impersonator_id' => auth()->id()]);
        auth()->login($user);

        return response()->json([
            'message' => "Now impersonating {$user->name}",
            'redirect' => '/',
        ]);
    }

    public function stopImpersonating(): JsonResponse
    {
        $originalId = session('impersonator_id');
        
        if (!$originalId) {
            return response()->json([
                'message' => 'Not currently impersonating',
            ], 422);
        }

        auth()->loginUsingId($originalId);
        session()->forget('impersonator_id');

        return response()->json([
            'message' => 'Stopped impersonating',
            'redirect' => '/admin',
        ]);
    }
}
