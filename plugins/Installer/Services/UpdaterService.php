<?php

namespace Plugins\Installer\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class UpdaterService
{
    private InstallerService $installer;

    public function __construct()
    {
        $this->installer = new InstallerService;
    }

    public function checkForUpdate(): array
    {
        $current = config('version.current', '1.0.0');

        $release = Cache::remember('church_platform_latest_release', 3600, function () {
            $response = Http::withHeaders(['User-Agent' => 'ChurchPlatform-Updater'])
                ->get(config('version.releases_api'));

            return $response->ok() ? $response->json() : null;
        });

        $latest = ltrim($release['tag_name'] ?? $current, 'v');

        return [
            'current' => $current,
            'latest' => $latest,
            'update_available' => version_compare($latest, $current, '>'),
            'release_url' => $release['html_url'] ?? '#',
            'release_notes' => $release['body'] ?? '',
        ];
    }

    public function checkConcurrency(): void
    {
        if (file_exists(storage_path('updating.lock'))) {
            throw new \RuntimeException('Update already in progress. If stuck, delete storage/updating.lock.');
        }
    }

    public function writeLock(): void
    {
        file_put_contents(storage_path('updating.lock'), now()->toIso8601String());
    }

    public function releaseLock(): void
    {
        @unlink(storage_path('updating.lock'));
    }

    public function maintenanceOn(): void
    {
        Artisan::call('down');
    }

    public function maintenanceOff(): void
    {
        Artisan::call('up');
    }

    /** Returns 'git' or 'zip' indicating which method was used. */
    public function pullLatestCode(): string
    {
        if (is_dir(base_path('.git'))) {
            // Use absolute git path — web server PATH is minimal
            Process::run(['/usr/bin/git', 'pull', 'origin', 'main', '--ff-only'],
                ['cwd' => base_path()])->throw();

            return 'git';
        }

        $this->pullViaZip();

        return 'zip';
    }

    private function pullViaZip(): void
    {
        $release = Cache::get('church_platform_latest_release');
        $zipUrl = $release['zipball_url'] ?? null;
        if (! $zipUrl) {
            throw new \RuntimeException('No ZIP URL available in release data.');
        }

        $tmpFile = sys_get_temp_dir().'/church_update_'.uniqid().'.zip';
        $tmpDir = sys_get_temp_dir().'/church_update_'.uniqid();

        file_put_contents($tmpFile,
            Http::withHeaders(['User-Agent' => 'ChurchPlatform-Updater'])->get($zipUrl)->body()
        );

        $zip = new \ZipArchive;
        $zip->open($tmpFile);
        $zip->extractTo($tmpDir);
        $zip->close();

        $extracted = glob($tmpDir.'/*', GLOB_ONLYDIR)[0] ?? $tmpDir;
        $this->copyDirectory($extracted, base_path(), ['.env', 'storage', 'public/build', 'public/storage']);

        unlink($tmpFile);
        $this->removeDirectory($tmpDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    private function copyDirectory(string $src, string $dest, array $skip = []): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $item) {
            $rel = str_replace($src.DIRECTORY_SEPARATOR, '', $item->getPathname());
            foreach ($skip as $s) {
                if (str_starts_with($rel, $s)) {
                    continue 2;
                }
            }
            $target = $dest.DIRECTORY_SEPARATOR.$rel;
            $item->isDir() ? @mkdir($target, 0755, true) : copy($item->getPathname(), $target);
        }
    }

    public function composerInstall(): void
    {
        // PHP_BINARY — same PHP version as the web app.
        // composer.phar is at base_path() — adjust path if your deployment puts it elsewhere.
        Process::run([PHP_BINARY, base_path('composer.phar'), 'install',
            '--no-dev', '--optimize-autoloader', '--no-interaction',
        ], ['cwd' => base_path()])->throw();
    }

    public function runMigrations(): void
    {
        Process::run([PHP_BINARY, base_path('artisan'), 'migrate', '--force'])->throw();
    }

    public function warmCaches(): void
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
    }

    public function writeNewVersion(string $version): void
    {
        $this->installer->updateEnv(['APP_VERSION' => $version]);
    }

    /**
     * Run full update sequence, emitting SSE-compatible events via $emit callable.
     * $emit signature: fn(string $step, string $status, string $message)
     * On any failure: site is brought back online and lock is released before re-throwing.
     */
    public function runUpdate(callable $emit): void
    {
        $this->checkConcurrency();
        $this->writeLock();

        try {
            $info = $this->checkForUpdate();

            $emit('maintenanceOn', 'running', 'Enabling maintenance mode...');
            $this->maintenanceOn();
            $emit('maintenanceOn', 'done', 'Maintenance mode ON');

            $emit('pullCode', 'running', 'Pulling latest code...');
            $method = $this->pullLatestCode();
            $emit('pullCode', 'done', "Code updated via {$method}");

            $emit('composer', 'running', 'Installing dependencies...');
            $this->composerInstall();
            $emit('composer', 'done', 'Dependencies installed');

            $emit('migrate', 'running', 'Running database migrations...');
            $this->runMigrations();
            $emit('migrate', 'done', 'Migrations complete');

            $emit('cache', 'running', 'Warming caches...');
            $this->warmCaches();
            $emit('cache', 'done', 'Caches warmed');

            $emit('maintenanceOff', 'running', 'Taking site back online...');
            $this->maintenanceOff();
            $emit('maintenanceOff', 'done', 'Site is online');

            $this->writeNewVersion($info['latest']);
            $emit('complete', 'done', "Update complete - v{$info['latest']}");

        } catch (\Throwable $e) {
            try {
                $this->maintenanceOff();
            } catch (\Throwable) {
            }
            $emit('error', 'error', 'Error: '.$e->getMessage());
            throw $e;
        } finally {
            $this->releaseLock();
        }
    }
}
