<?php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PermissionController extends Controller
{
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        $grouped = $permissions->groupBy('group')->map(fn ($perms) => $perms->values());

        return response()->json([
            'permissions' => $permissions,
            'grouped' => $grouped,
        ]);
    }
}
