<?php

namespace Plugins\Installer;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class InstallerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'installer');

        $router = $this->app->make(Router::class);

        // Installer: web middleware, only when not yet installed
        if (! file_exists(storage_path('installed.lock'))) {
            $router->middleware('web')
                ->prefix('install')
                ->group(base_path('plugins/Installer/routes/installer.php'));
        }

        // Updater: always registered, guarded by auth + admin role
        $router->middleware(['web', 'auth', 'role:admin'])
            ->prefix('update')
            ->group(base_path('plugins/Installer/routes/updater.php'));
    }

    public function register(): void
    {
        $this->commands([
            Commands\InstallCommand::class,
            Commands\UpdateCommand::class,
        ]);
    }
}
