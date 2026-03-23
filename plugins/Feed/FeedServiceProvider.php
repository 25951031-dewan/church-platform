<?php

namespace Plugins\Feed;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class FeedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->middleware('api')->prefix('api')->group(__DIR__.'/routes/api.php');
    }
}
