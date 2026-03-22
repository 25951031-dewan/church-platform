<?php

namespace App\Http\Middleware;

use App\Services\PlatformModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePlatformMode
{
    public function __construct(private readonly PlatformModeService $platform) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Bind the resolved church context into the request so downstream
        // controllers can access it via $request->get('church_id') or
        // app(PlatformModeService::class)->defaultChurch().
        if ($this->platform->isSingleChurch() && $this->platform->defaultChurch()) {
            $request->merge(['church_id' => $this->platform->defaultChurch()]);
        }

        return $next($request);
    }
}
