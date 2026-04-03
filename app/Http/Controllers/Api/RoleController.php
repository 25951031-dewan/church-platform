<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')->get();
        return response()->json(['data' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);
        $validated['slug'] = Str::slug($validated['name']);
        $role = Role::create($validated);
        return response()->json(['message' => 'Role created.', 'data' => $role], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $role->update($validated);
        return response()->json(['message' => 'Role updated.', 'data' => $role->fresh()]);
    }

    public function destroy(Role $role): JsonResponse
    {
        User::where('role_id', $role->id)->update(['role_id' => null]);
        $role->delete();
        return response()->json(['message' => 'Role deleted.']);
    }

    public function assignRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'nullable|exists:roles,id',
        ]);
        $user = User::findOrFail($validated['user_id']);
        $user->update(['role_id' => $validated['role_id']]);
        return response()->json(['message' => 'Role assigned.', 'data' => $user->fresh()->load('role')]);
    }
}
