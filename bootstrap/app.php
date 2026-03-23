<?php

use App\Http\Middleware\ResolvePlatformMode;
use App\Http\Middleware\TrackPageView;
use App\Http\Middleware\VerifyCaptcha;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'platform.mode' => ResolvePlatformMode::class,
            'captcha' => VerifyCaptcha::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->appendToGroup('api', ResolvePlatformMode::class);
        $middleware->appendToGroup('web', TrackPageView::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
