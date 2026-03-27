<?php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name,display_name,group')
            ->orderByDesc('level')
            ->get();

        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'in:system,church,custom',
            'level' => 'integer|min:1|max:99',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'] ?? 'custom',
            'level' => $validated['level'] ?? 10,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'role' => $role->load('permissions:id,name,display_name,group'),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'level' => 'integer|min:1|max:99',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->update(collect($validated)->except('permissions')->toArray());

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);

            // Clear permission cache for all users with this role
            $role->users->each(fn ($user) => $user->clearPermissionCache());
        }

        return response()->json([
            'role' => $role->fresh()->load('permissions:id,name,display_name,group'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->type === 'system') {
            return response()->json(['message' => 'Cannot delete system roles'], 403);
        }

        $role->delete();
        return response()->noContent();
    }
}
