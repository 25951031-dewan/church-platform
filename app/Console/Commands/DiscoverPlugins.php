<?php

namespace App\Console\Commands;

use App\Services\PluginManager;
use Illuminate\Console\Command;

class DiscoverPlugins extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:discover {--clear : Clear the plugin cache before discovering}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover and cache all available plugins';

    /**
     * Execute the console command.
     */
    public function handle(PluginManager $pluginManager): int
    {
        $this->info('Discovering plugins...');

        if ($this->option('clear')) {
            $pluginManager->clearCache();
            $this->info('Plugin cache cleared.');
        }

        $plugins = $pluginManager->discoverPlugins();

        if (empty($plugins)) {
            $this->warn('No plugins found.');
            return Command::SUCCESS;
        }

        $this->info('Found ' . count($plugins) . ' plugin(s):');
        $this->newLine();

        $rows = [];
        foreach ($plugins as $pluginName => $plugin) {
            $isEnabled = $pluginManager->isPluginEnabled($pluginName);
            $rows[] = [
                $plugin['name'],
                $plugin['version'],
                $isEnabled ? '✓ Enabled' : '✗ Disabled',
                $plugin['description'],
            ];
        }

        $this->table(
            ['Name', 'Version', 'Status', 'Description'],
            $rows
        );

        $this->newLine();
        $this->info('Plugin discovery complete.');

        return Command::SUCCESS;
    }
}
