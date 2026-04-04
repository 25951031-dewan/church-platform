<?php

namespace App\Plugins\Prayer;

use App\Contracts\PluginInterface;

class Plugin implements PluginInterface
{
    public function getName(): string
    {
        return 'Prayer';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Prayer plugin for Church Platform';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function boot(): void
    {
        // Plugin boot logic
    }
}
