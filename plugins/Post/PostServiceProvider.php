<?php

namespace Plugins\Post;

use Illuminate\Support\ServiceProvider;

class PostServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->middleware('api')->prefix('api')->group(__DIR__.'/routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
