<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Get current system status: git info, PHP version, Laravel version, etc.
     */
    public function status(): JsonResponse
    {
        $info = [];

        // App version from config or git
        $info['app_name'] = config('app.name', 'Church Platform');
        $info['app_version'] = config('version.version', '1.0.0');
        $info['release_date'] = config('version.release_date');
        $info['laravel_version'] = app()->version();
        $info['php_version'] = PHP_VERSION;
        $info['environment'] = app()->environment();
        $info['debug_mode'] = config('app.debug');

        // Git info
        $info['git'] = $this->getGitInfo();

        // Server info
        $info['server'] = [
            'os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'disk_free' => $this->formatBytes(@disk_free_space(base_path())),
            'disk_total' => $this->formatBytes(@disk_total_space(base_path())),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ];

        // Database
        try {
            \DB::connection()->getPdo();
            $info['database'] = [
                'connected' => true,
                'driver' => config('database.default'),
            ];
        } catch (\Exception $e) {
            $info['database'] = ['connected' => false, 'error' => $e->getMessage()];
        }

        // Cache & queue
        $info['cache_driver'] = config('cache.default');
        $info['queue_driver'] = config('queue.default');
        $info['session_driver'] = config('session.driver');

        return response()->json(['success' => true, 'data' => $info]);
    }

    /**
     * Pull latest code from git remote.
     */
    public function gitPull(): JsonResponse
    {
        $basePath = base_path();

        // Check if git is available
        if (!$this->commandExists('git')) {
            return response()->json([
                'success' => false,
                'message' => 'Git is not installed on this server.',
            ], 500);
        }

        // Check for uncommitted changes
        $statusOutput = $this->runCommand("cd {$basePath} && git status --porcelain 2>&1");
        if (!empty(trim($statusOutput))) {
            return response()->json([
                'success' => false,
                'message' => 'There are uncommitted changes. Please commit or stash them first.',
                'data' => ['uncommitted_changes' => $statusOutput],
            ], 422);
        }

        // Get current branch
        $branch = trim($this->runCommand("cd {$basePath} && git rev-parse --abbrev-ref HEAD 2>&1"));

        // Fetch first
        $fetchOutput = $this->runCommand("cd {$basePath} && git fetch origin 2>&1");

        // Check if there are updates available
        $localHash = trim($this->runCommand("cd {$basePath} && git rev-parse HEAD 2>&1"));
        $remoteHash = trim($this->runCommand("cd {$basePath} && git rev-parse origin/{$branch} 2>&1"));

        if ($localHash === $remoteHash) {
            return response()->json([
                'success' => true,
                'message' => 'Already up to date. No new changes to pull.',
                'data' => [
                    'branch' => $branch,
                    'commit' => substr($localHash, 0, 8),
                    'updated' => false,
                ],
            ]);
        }

        // Pull
        $pullOutput = $this->runCommand("cd {$basePath} && git pull origin {$branch} 2>&1");
        $newHash = trim($this->runCommand("cd {$basePath} && git rev-parse HEAD 2>&1"));

        // Get commit log between old and new
        $changelog = $this->runCommand("cd {$basePath} && git log --oneline {$localHash}..{$newHash} 2>&1");

        Log::info('System update: git pull completed', [
            'branch' => $branch,
            'old_commit' => $localHash,
            'new_commit' => $newHash,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Code updated successfully!',
            'data' => [
                'branch' => $branch,
                'old_commit' => substr($localHash, 0, 8),
                'new_commit' => substr($newHash, 0, 8),
                'updated' => true,
                'pull_output' => $pullOutput,
                'changelog' => $changelog,
            ],
        ]);
    }

    /**
     * Run database migrations.
     */
    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            Log::info('System update: migrations completed', ['output' => $output]);

            return response()->json([
                'success' => true,
                'message' => 'Database migrations completed.',
                'data' => ['output' => $output],
            ]);
        } catch (\Exception $e) {
            Log::error('System update: migration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build frontend assets (npm run build).
     */
    public function buildAssets(): JsonResponse
    {
        $basePath = base_path();

        if (!file_exists($basePath . '/node_modules')) {
            // Run npm install first
            $installOutput = $this->runCommand("cd {$basePath} && npm install 2>&1", 300);
        }

        $output = $this->runCommand("cd {$basePath} && npm run build 2>&1", 300);

        if (strpos($output, 'error') !== false && strpos($output, 'ERROR') !== false) {
            return response()->json([
                'success' => false,
                'message' => 'Asset build had errors. Check output.',
                'data' => ['output' => $output],
            ], 500);
        }

        Log::info('System update: assets built', ['output' => substr($output, 0, 500)]);

        return response()->json([
            'success' => true,
            'message' => 'Frontend assets built successfully.',
            'data' => ['output' => $output],
        ]);
    }

    /**
     * Clear all caches.
     */
    public function clearCache(): JsonResponse
    {
        $results = [];

        try {
            Artisan::call('cache:clear');
            $results['cache'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['cache'] = 'Error: ' . $e->getMessage();
        }

        try {
            Artisan::call('config:clear');
            $results['config'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['config'] = 'Error: ' . $e->getMessage();
        }

        try {
            Artisan::call('route:clear');
            $results['route'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['route'] = 'Error: ' . $e->getMessage();
        }

        try {
            Artisan::call('view:clear');
            $results['view'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['view'] = 'Error: ' . $e->getMessage();
        }

        Log::info('System update: caches cleared', $results);

        return response()->json([
            'success' => true,
            'message' => 'All caches cleared successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Optimize the application (cache config, routes, views).
     */
    public function optimize(): JsonResponse
    {
        $results = [];

        try {
            Artisan::call('config:cache');
            $results['config'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['config'] = 'Error: ' . $e->getMessage();
        }

        try {
            Artisan::call('route:cache');
            $results['route'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['route'] = 'Error: ' . $e->getMessage();
        }

        try {
            Artisan::call('view:cache');
            $results['view'] = trim(Artisan::output());
        } catch (\Exception $e) {
            $results['view'] = 'Error: ' . $e->getMessage();
        }

        Log::info('System update: optimized', $results);

        return response()->json([
            'success' => true,
            'message' => 'Application optimized for production.',
            'data' => $results,
        ]);
    }

    /**
     * Full deploy pipeline: pull → migrate → build → clear cache → optimize.
     */
    public function deploy(): JsonResponse
    {
        $steps = [];
        $hasError = false;

        // Step 1: Git Pull
        $pullResult = $this->gitPull();
        $pullData = json_decode($pullResult->getContent(), true);
        $steps[] = [
            'step' => 'Git Pull',
            'success' => $pullData['success'],
            'message' => $pullData['message'],
        ];
        if (!$pullData['success']) {
            $hasError = true;
        }

        // Step 2: Install Composer Dependencies (if composer.lock changed)
        $basePath = base_path();
        if ($this->commandExists('composer')) {
            $composerOutput = $this->runCommand("cd {$basePath} && composer install --no-dev --optimize-autoloader 2>&1", 300);
            $steps[] = [
                'step' => 'Composer Install',
                'success' => true,
                'message' => 'Dependencies updated.',
            ];
        }

        // Step 3: Migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = [
                'step' => 'Database Migrations',
                'success' => true,
                'message' => trim(Artisan::output()),
            ];
        } catch (\Exception $e) {
            $steps[] = [
                'step' => 'Database Migrations',
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $hasError = true;
        }

        // Step 4: Build Assets
        if (file_exists($basePath . '/package.json')) {
            $buildOutput = $this->runCommand("cd {$basePath} && npm run build 2>&1", 300);
            $buildSuccess = strpos($buildOutput, 'error') === false || strpos($buildOutput, 'ERROR') === false;
            $steps[] = [
                'step' => 'Build Assets',
                'success' => $buildSuccess,
                'message' => $buildSuccess ? 'Assets built successfully.' : 'Build had errors.',
            ];
        }

        // Step 5: Clear & Optimize
        try {
            Artisan::call('optimize:clear');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            $steps[] = [
                'step' => 'Cache & Optimize',
                'success' => true,
                'message' => 'Application optimized.',
            ];
        } catch (\Exception $e) {
            $steps[] = [
                'step' => 'Cache & Optimize',
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        // Step 6: Storage link
        if (!file_exists(public_path('storage'))) {
            try {
                Artisan::call('storage:link');
                $steps[] = [
                    'step' => 'Storage Link',
                    'success' => true,
                    'message' => 'Storage linked.',
                ];
            } catch (\Exception $e) {
                // Non-critical
            }
        }

        Log::info('System deploy completed', ['steps' => $steps, 'hasError' => $hasError]);

        return response()->json([
            'success' => !$hasError,
            'message' => $hasError ? 'Deploy completed with some errors.' : 'Deploy completed successfully!',
            'data' => ['steps' => $steps],
        ]);
    }

    /**
     * Get git log (recent commits).
     */
    public function gitLog(): JsonResponse
    {
        $basePath = base_path();
        $log = $this->runCommand("cd {$basePath} && git log --oneline -20 2>&1");
        $branch = trim($this->runCommand("cd {$basePath} && git rev-parse --abbrev-ref HEAD 2>&1"));

        return response()->json([
            'success' => true,
            'data' => [
                'branch' => $branch,
                'log' => $log,
            ],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────

    private function getGitInfo(): array
    {
        $basePath = base_path();
        if (!is_dir($basePath . '/.git')) {
            return ['available' => false, 'message' => 'Not a git repository'];
        }

        $branch = trim($this->runCommand("cd {$basePath} && git rev-parse --abbrev-ref HEAD 2>&1"));
        $commit = trim($this->runCommand("cd {$basePath} && git rev-parse --short HEAD 2>&1"));
        $lastCommitMsg = trim($this->runCommand("cd {$basePath} && git log -1 --pretty=%B 2>&1"));
        $lastCommitDate = trim($this->runCommand("cd {$basePath} && git log -1 --pretty=%ci 2>&1"));
        $remoteUrl = trim($this->runCommand("cd {$basePath} && git remote get-url origin 2>&1"));

        // Check if behind remote
        $this->runCommand("cd {$basePath} && git fetch origin 2>&1");
        $behind = trim($this->runCommand("cd {$basePath} && git rev-list --count HEAD..origin/{$branch} 2>&1"));

        return [
            'available' => true,
            'branch' => $branch,
            'commit' => $commit,
            'last_message' => $lastCommitMsg,
            'last_date' => $lastCommitDate,
            'remote_url' => $remoteUrl,
            'behind' => is_numeric($behind) ? (int) $behind : 0,
        ];
    }

    private function runCommand(string $command, int $timeout = 60): string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return 'Failed to execute command';
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return $stdout ?: $stderr;
    }

    private function commandExists(string $command): bool
    {
        $output = $this->runCommand("which {$command} 2>/dev/null");
        return !empty(trim($output));
    }

    private function formatBytes($bytes): string
    {
        if ($bytes === false || $bytes === null) return 'N/A';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
