<?php

namespace App\Modules;

use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * The module identifier key (matches config/modules.php).
     */
    protected string $moduleName = '';

    public function register(): void
    {
        if ($this->moduleName && !app(ModuleManager::class)->isActive($this->moduleName)) {
            return;
        }
        $this->registerModule();
    }

    public function boot(): void
    {
        if ($this->moduleName && !app(ModuleManager::class)->isActive($this->moduleName)) {
            return;
        }
        $this->bootModule();
    }

    /**
     * Override in concrete module providers to register bindings.
     */
    protected function registerModule(): void
    {
        //
    }

    /**
     * Override in concrete module providers to load routes, views, etc.
     */
    protected function bootModule(): void
    {
        $this->loadModuleRoutes();
    }

    /**
     * Load api.php and web.php routes from the module's Routes directory.
     */
    protected function loadModuleRoutes(): void
    {
        $class = new \ReflectionClass(static::class);
        $dir = dirname($class->getFileName()) . '/Routes';

        if (file_exists($dir . '/api.php')) {
            $this->loadRoutesFrom($dir . '/api.php');
        }
        if (file_exists($dir . '/web.php')) {
            $this->loadRoutesFrom($dir . '/web.php');
        }
    }
}
