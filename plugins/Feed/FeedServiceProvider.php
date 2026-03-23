<?php
namespace Plugins\Feed;
use Illuminate\Support\ServiceProvider;
class FeedServiceProvider extends ServiceProvider {
    public function boot(): void {
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->middleware('api')->prefix('api')->group(__DIR__ . '/routes/api.php');
    }
}
