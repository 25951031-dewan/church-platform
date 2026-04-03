<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class SeoMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if (file_exists(storage_path('installed'))) {
                $settings = Setting::first();
                if ($settings) {
                    view()->share('seoSettings', $settings);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if settings table doesn't exist yet
        }

        return $next($request);
    }
}
