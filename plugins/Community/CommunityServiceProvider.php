<?php

namespace Plugins\Community;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')->group(base_path('plugins/Community/routes/api.php'));
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
