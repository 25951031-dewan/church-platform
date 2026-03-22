<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyCaptcha
{
    private const TURNSTILE_VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function handle(Request $request, Closure $next): Response
    {
        $settings = DB::table('settings')->where('key', 'captcha')->first();

        // Skip if captcha is disabled
        if (! $settings || ! $settings->captcha_enabled) {
            return $next($request);
        }

        $token = $request->input('cf-turnstile-response');

        if (empty($token)) {
            return response()->json(['message' => 'Captcha token missing.'], 422);
        }

        $verified = $this->verifyTurnstile(
            $token,
            $settings->turnstile_secret_key,
            $request->ip()
        );

        if (! $verified) {
            return response()->json(['message' => 'Captcha verification failed.'], 422);
        }

        return $next($request);
    }

    private function verifyTurnstile(string $token, ?string $secret, string $ip): bool
    {
        if (empty($secret)) {
            return false;
        }

        $response = Http::asForm()->post(self::TURNSTILE_VERIFY_URL, [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]);

        return $response->successful() && $response->json('success') === true;
    }
}
