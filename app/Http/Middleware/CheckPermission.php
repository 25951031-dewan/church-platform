<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckPermission
{
    /**
     * Usage: ->middleware('permission:manage_sermons')
     * Super Admin (is_admin=true) always passes.
     * Permissions are cached per user for 5 minutes.
     */
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json(['message' => 'Insufficient permissions.'], 403);
    }
}
