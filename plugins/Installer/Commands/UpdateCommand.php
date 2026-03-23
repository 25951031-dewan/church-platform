<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;

class UpdateCommand extends Command
{
    protected $signature = 'church:update';

    protected $description = 'Update the Church Platform.';

    public function handle(): int
    {
        $this->info('Updater not yet implemented.');

        return self::SUCCESS;
    }
}
