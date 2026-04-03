<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DetectDevice
{
    /**
     * Detect mobile/desktop from User-Agent.
     * Also detects explicit PWA requests via header or query param.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $isPwa = $request->hasHeader('X-PWA-Request')
            || $request->query('pwa') === '1';

        $isMobile = $isPwa || $this->isMobileAgent($request->userAgent() ?? '');

        $request->attributes->set('is_pwa', $isPwa);
        $request->attributes->set('is_mobile', $isMobile);

        return $next($request);
    }

    protected function isMobileAgent(string $ua): bool
    {
        return (bool) preg_match(
            '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
            $ua
        );
    }
}
