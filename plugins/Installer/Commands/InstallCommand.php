<?php

namespace Plugins\Installer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Plugins\Installer\Services\InstallerService;

class InstallCommand extends Command
{
    protected $signature = 'church:install';

    protected $description = 'Run the Church Platform 3-step installer interactively.';

    public function handle(InstallerService $service): int
    {
        if (file_exists(storage_path('installed.lock'))) {
            $this->error('Already installed. Delete storage/installed.lock to re-run (then clear route cache).');

            return self::FAILURE;
        }

        $this->info('');
        $this->line('  Church Platform Installer');
        $this->info('');

        // Step 1: Requirements
        $this->line('  Step 1 - Requirements');
        $service->prepareEnvironment();
        $checks = $service->checkRequirements();
        foreach ($checks as $key => $pass) {
            $this->line('  '.($pass ? '[OK]' : '[FAIL]').'  '.$key);
        }
        if (in_array(false, $checks, true)) {
            $this->error('Fix failed requirements before running the installer.');

            return self::FAILURE;
        }
        $appUrl = $this->ask('App URL', 'http://localhost');
        $service->writeStep1Env($appUrl);
        $this->line('  APP_KEY generated');
        $this->info('');

        // Step 2: Database
        $this->line('  Step 2 - Database');
        $appName = $this->ask('App Name', 'Church Platform');
        $dbHost = $this->ask('DB Host', '127.0.0.1');
        $dbPort = $this->ask('DB Port', '3306');
        $dbName = $this->ask('DB Name', 'church_platform');
        $dbUser = $this->ask('DB Username', 'root');
        $dbPass = $this->secret('DB Password');

        if (! $service->testConnection([
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass ?? '',
        ])) {
            $this->error('Could not connect to database. Check credentials and try again.');

            return self::FAILURE;
        }

        $service->updateEnv([
            'APP_NAME' => $appName,
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $dbHost,
            'DB_PORT' => $dbPort,
            'DB_DATABASE' => $dbName,
            'DB_USERNAME' => $dbUser,
            'DB_PASSWORD' => $dbPass ?? '',
        ]);
        Artisan::call('config:clear');
        $this->line('  Running migrations...');
        $service->runMigrations();
        $this->line('  Migrations complete');
        $this->info('');

        // Step 3: Admin Account
        $this->line('  Step 3 - Admin Account');
        $adminName = $this->ask('Admin Name');
        $adminEmail = $this->ask('Admin Email');
        $adminPass = $this->secret('Admin Password');

        $service->seedRoles();
        $admin = $service->createAdmin([
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => $adminPass,
        ]);
        $service->createDefaultChurch($appName, $admin->id);
        $service->createStorageLink();

        $service->updateEnv([
            'APP_INSTALLED' => 'true',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'sync',
        ]);

        $service->lockInstaller();   // MUST be before warmCaches
        $service->warmCaches();

        $this->info('');
        $this->line("  Installation complete! Visit: {$appUrl}");
        $this->info('');

        return self::SUCCESS;
    }
}
