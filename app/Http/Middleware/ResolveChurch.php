<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ChurchContext;

class ResolveChurch
{
    public function __construct(protected ChurchContext $context)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (config('app.church_mode', 'single') !== 'multi') {
            return $next($request);
        }

        $churchId = $this->resolveChurchId($request);
        $this->context->setId($churchId);

        return $next($request);
    }

    protected function resolveChurchId(Request $request): ?int
    {
        // 1. Authenticated user's church_id (most reliable for API)
        $user = $request->user();
        if ($user && $user->church_id) {
            return (int) $user->church_id;
        }

        // 2. X-Church-Id header (for multi-church API clients)
        if ($request->hasHeader('X-Church-Id')) {
            return (int) $request->header('X-Church-Id');
        }

        // 3. Route parameter {church_id}
        if ($request->route('church_id')) {
            return (int) $request->route('church_id');
        }

        // 4. Subdomain: church-slug.domain.com
        $host = $request->getHost();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        if ($appHost && str_ends_with($host, '.' . $appHost)) {
            $subdomain = str_replace('.' . $appHost, '', $host);
            if ($subdomain && $subdomain !== 'www') {
                $church = \App\Models\Church::where('slug', $subdomain)
                    ->where('status', 'approved')
                    ->first();
                if ($church) {
                    return $church->id;
                }
            }
        }

        // 5. Default: null (global/single-church context)
        return null;
    }
}
