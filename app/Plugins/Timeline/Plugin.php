<?php

namespace App\Plugins\Timeline;

use App\Contracts\PluginInterface;

class Plugin implements PluginInterface
{
    public function getName(): string
    {
        return 'Timeline';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Timeline plugin for Church Platform';
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
