<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'permission:admin.access']);
    }

    public function index(): JsonResponse
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name,slug,group')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions,
                ];
            });

        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $role = Role::create(collect($validated)->except('permissions')->toArray());

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role->load('permissions'),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => "sometimes|required|string|max:255|unique:roles,name,{$role->id}",
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $role->update(collect($validated)->except('permissions')->toArray());

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role->fresh('permissions'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        // Prevent deletion of super admin role
        if ($role->slug === 'super_admin') {
            return response()->json([
                'message' => 'Cannot delete super admin role.',
            ], 422);
        }

        // Detach users from this role
        $role->users()->detach();
        
        // Delete the role
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
