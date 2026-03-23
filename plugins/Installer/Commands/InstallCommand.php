<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'church:install';

    protected $description = 'Run the Church Platform 3-step installer.';

    public function handle(): int
    {
        $this->info('Installer not yet implemented.');

        return self::SUCCESS;
    }
}
