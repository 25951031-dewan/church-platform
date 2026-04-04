<?php

namespace App\Plugins\Marketplace;

use App\Contracts\PluginInterface;

class Plugin implements PluginInterface
{
    public function getName(): string
    {
        return 'Marketplace';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'Community marketplace for buying, selling, and trading items within the church community.';
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function boot(): void
    {
        //
    }
}
