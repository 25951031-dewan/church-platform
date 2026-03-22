<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Analytics\Models\PageView;
use Symfony\Component\HttpFoundation\Response;

class TrackPageView
{
    // Paths to skip tracking (admin, API internals, assets)
    private const SKIP_PREFIXES = [
        '/api/v1/admin',
        '/api/v1/captcha',
        '/_debugbar',
        '/telescope',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track successful GET requests
        if ($request->method() !== 'GET' || $response->getStatusCode() >= 400) {
            return $response;
        }

        $path = $request->path();
        foreach (self::SKIP_PREFIXES as $prefix) {
            if (str_starts_with('/'.$path, $prefix)) {
                return $response;
            }
        }

        // Fire-and-forget: wrap in try/catch so analytics never breaks the app
        try {
            PageView::create([
                'url'        => '/'.$path,
                'user_id'    => $request->user()?->id,
                'session_id' => $request->session()->getId(),
                'ip_hash'    => hash('sha256', $request->ip()),
                'user_agent' => substr($request->userAgent() ?? '', 0, 255),
                'referrer'   => substr($request->header('referer', ''), 0, 500),
                'church_id'  => $request->get('church_id'),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Silently swallow — analytics must never affect page delivery
        }

        return $response;
    }
}
