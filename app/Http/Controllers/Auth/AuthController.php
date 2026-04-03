<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $rules = [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'church_name'           => ['nullable', 'string', 'max:255'],
            'social_id'             => ['nullable', 'string', 'max:255'],
            'spiritual_background'  => ['nullable', 'string', 'max:2000'],
            'custom_fields'         => ['nullable', 'array'],
        ];

        // Apply required rules from custom profile fields config
        $setting = Setting::first();
        $profileFields = $setting->custom_profile_fields ?? [];
        foreach ($profileFields as $field) {
            if (($field['enabled'] ?? false) && ($field['required'] ?? false)) {
                $key = $field['key'] ?? '';
                if (in_array($key, ['phone', 'church_name', 'social_id', 'spiritual_background'])) {
                    $rules[$key][0] = 'required';
                } else {
                    $rules["custom_fields.{$key}"] = ['required', 'string', 'max:2000'];
                }
            }
        }

        $validated = $request->validate($rules);

        $user = User::create([
            'name'                  => $validated['name'],
            'email'                 => $validated['email'],
            'password'              => Hash::make($validated['password']),
            'phone'                 => $validated['phone'] ?? null,
            'church_name'           => $validated['church_name'] ?? null,
            'social_id'             => $validated['social_id'] ?? null,
            'spiritual_background'  => $validated['spiritual_background'] ?? null,
            'custom_fields'         => $validated['custom_fields'] ?? null,
            'profile_completed'     => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }

    /**
     * Authenticate user and issue token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($validated)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user  = User::where('email', $validated['email'])->firstOrFail();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user'    => $user,
            'token'   => $token,
        ]);
    }

    /**
     * Revoke current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'                  => ['sometimes', 'required', 'string', 'max:255'],
            'email'                 => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'              => ['sometimes', 'required', 'confirmed', Rules\Password::defaults()],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'church_name'           => ['nullable', 'string', 'max:255'],
            'social_id'             => ['nullable', 'string', 'max:255'],
            'spiritual_background'  => ['nullable', 'string', 'max:2000'],
            'custom_fields'         => ['nullable', 'array'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }

    /**
     * Redirect to social provider for OAuth.
     */
    public function socialRedirect(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle social provider callback.
     */
    public function socialCallback(string $provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/?auth_error=' . urlencode('Social login failed. Please try again.'));
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar'      => $user->avatar ?: $socialUser->getAvatar(),
                ]);
            } else {
                $user = User::create([
                    'name'        => $socialUser->getName(),
                    'email'       => $socialUser->getEmail(),
                    'provider'    => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar'      => $socialUser->getAvatar(),
                    'password'    => Hash::make(str()->random(24)),
                ]);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return redirect('/?auth_token=' . $token . '&auth_user=' . urlencode(json_encode([
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'avatar'   => $user->avatar,
            'is_admin' => $user->is_admin,
        ])));
    }
}
