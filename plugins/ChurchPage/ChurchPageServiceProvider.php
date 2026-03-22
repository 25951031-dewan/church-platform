<?php

namespace Plugins\ChurchPage;

use Illuminate\Support\ServiceProvider;

class ChurchPageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
