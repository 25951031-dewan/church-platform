<?php
namespace Plugins\Reaction;
use Illuminate\Support\ServiceProvider;
class ReactionServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');
    }
}
