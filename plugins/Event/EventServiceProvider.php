<?php
namespace Plugins\Event;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // CRITICAL: Use Router directly so routes get the 'api' middleware and 'api/' prefix
        // from bootstrap/app.php. loadRoutesFrom() bypasses these.
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')->group(base_path('plugins/Event/routes/api.php'));

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
