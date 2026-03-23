<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;
use Plugins\Installer\Services\UpdaterService;

class UpdateCommand extends Command
{
    protected $signature = 'church:update';

    protected $description = 'Update the Church Platform to the latest version.';

    public function handle(UpdaterService $service): int
    {
        try {
            $service->checkConcurrency();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $info = $service->checkForUpdate();
        $this->info('');
        $this->line("  Current version: {$info['current']}");
        $this->line("  Latest version:  {$info['latest']}");
        $this->info('');

        if (! $info['update_available']) {
            $this->line("  Already on latest version ({$info['latest']}). No update needed.");

            return self::SUCCESS;
        }

        if (! $this->confirm("Update to v{$info['latest']}?", true)) {
            return self::SUCCESS;
        }

        $service->runUpdate(function (string $step, string $status, string $message) {
            $status === 'error' ? $this->error("  {$message}") : $this->line("  {$message}");
        });

        return self::SUCCESS;
    }
}
