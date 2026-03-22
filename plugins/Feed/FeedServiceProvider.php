<?php
namespace Plugins\Feed;
use Illuminate\Support\ServiceProvider;
class FeedServiceProvider extends ServiceProvider {
    public function boot(): void { $this->loadRoutesFrom(__DIR__ . '/routes/api.php'); }
}
