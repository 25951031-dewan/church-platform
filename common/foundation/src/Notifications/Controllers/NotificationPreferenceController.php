<?php

namespace Common\Notifications\Controllers;

use Common\Notifications\Models\NotificationPreference;
use Common\Notifications\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        foreach (array_keys(NotificationPreference::defaults()) as $type) {
            NotificationPreference::getForUser($user->id, $type);
        }

        return response()->json([
            'preferences' => NotificationPreference::where('user_id', $user->id)->get(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.notification_type' => 'required|string',
            'preferences.*.push_enabled' => 'sometimes|boolean',
            'preferences.*.email_enabled' => 'sometimes|boolean',
            'preferences.*.sms_enabled' => 'sometimes|boolean',
            'preferences.*.in_app_enabled' => 'sometimes|boolean',
        ]);

        foreach ($validated['preferences'] as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'notification_type' => $preference['notification_type'],
                ],
                [
                    'push_enabled' => $preference['push_enabled'] ?? true,
                    'email_enabled' => $preference['email_enabled'] ?? true,
                    'sms_enabled' => $preference['sms_enabled'] ?? false,
                    'in_app_enabled' => $preference['in_app_enabled'] ?? true,
                ]
            );
        }

        return $this->index($request);
    }

    public function registerPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_id' => 'required|string|max:255',
            'device_type' => 'nullable|in:web,ios,android',
            'device_name' => 'nullable|string|max:255',
        ]);

        PushSubscription::updateOrCreate(
            ['player_id' => $validated['player_id']],
            [
                'user_id' => $request->user()->id,
                'device_type' => $validated['device_type'] ?? 'web',
                'device_name' => $validated['device_name'] ?? null,
                'last_active_at' => now(),
            ]
        );

        return response()->json(['message' => 'Push subscription registered']);
    }

    public function unregisterPush(Request $request): JsonResponse
    {
        $validated = $request->validate(['player_id' => 'required|string|max:255']);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('player_id', $validated['player_id'])
            ->delete();

        return response()->json(['message' => 'Push subscription removed']);
    }
}
