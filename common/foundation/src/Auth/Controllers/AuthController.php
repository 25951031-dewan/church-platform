<?php

namespace Common\Auth\Controllers;

use Common\Auth\Models\Role;
use Common\Auth\Requests\LoginRequest;
use Common\Auth\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->isBanned()) {
            return response()->json(['message' => 'Account suspended'], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $user->getBootstrapData(),
            'token' => $token,
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $userModel = config('auth.providers.users.model');

        $user = $userModel::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Attach default role
        $defaultRole = Role::where('is_default', true)->first();
        if ($defaultRole) {
            $user->roles()->attach($defaultRole);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'user' => $user->getBootstrapData(),
            'token' => $token,
        ], 201);
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => Auth::user()->getBootstrapData(),
        ]);
    }
}
