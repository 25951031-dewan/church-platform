<?php

namespace App\Providers;

use App\Core\SettingsManager;
use App\Services\PlatformModeService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsManager::class);
        $this->app->singleton(PlatformModeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
