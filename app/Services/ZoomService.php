<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZoomService
{
    public function createMeeting(array $payload): array
    {
        $token = $this->accessToken();
        $userId = $payload['zoom_user_id'] ?? 'me';

        $response = Http::withToken($token)->post(
            "https://api.zoom.us/v2/users/{$userId}/meetings",
            [
                'topic' => $payload['topic'] ?? 'Church Meeting',
                'type' => 2,
                'start_time' => $payload['start_time'] ?? now()->toIso8601String(),
                'duration' => $payload['duration'] ?? 60,
                'timezone' => $payload['timezone'] ?? 'UTC',
                'agenda' => $payload['agenda'] ?? null,
                'settings' => $payload['settings'] ?? [],
            ]
        );

        return $response->json() ?? [];
    }

    private function accessToken(): ?string
    {
        $accountId = config('services.zoom.account_id');
        $clientId = config('services.zoom.client_id');
        $clientSecret = config('services.zoom.client_secret');

        if (!$accountId || !$clientId || !$clientSecret) {
            return null;
        }

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->asForm()
            ->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]);

        return $response->json('access_token');
    }
}
