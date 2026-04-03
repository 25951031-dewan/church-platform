<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installedFile = storage_path('installed');

        if (!file_exists($installedFile) && !$request->is('install*') && !$request->is('api/install*')) {
            return redirect('/install');
        }

        if (file_exists($installedFile) && $request->is('install*')) {
            return redirect('/');
        }

        return $next($request);
    }
}
