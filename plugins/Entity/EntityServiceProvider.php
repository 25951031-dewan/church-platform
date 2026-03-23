<?php
namespace Plugins\Entity;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EntityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Gate::policy(
            \Plugins\Entity\Models\ChurchEntity::class,
            \Plugins\Entity\Policies\ChurchEntityPolicy::class
        );
    }

    public function boot(): void
    {
        // CRITICAL: Use Router directly — loadRoutesFrom() bypasses api middleware + prefix
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')
               ->group(base_path('plugins/Entity/routes/api.php'));

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}
