<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:admin']);
    }

    public function info(): JsonResponse
    {
        return response()->json([
            'data' => [
                'app' => [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                    'debug' => config('app.debug'),
                    'url' => config('app.url'),
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ],
                'database' => [
                    'driver' => config('database.default'),
                    'version' => $this->getDatabaseVersion(),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                ],
                'queue' => [
                    'driver' => config('queue.default'),
                ],
                'mail' => [
                    'driver' => config('mail.default'),
                ],
            ],
        ]);
    }

    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            Log::info('Admin cleared all caches', ['user_id' => auth()->id()]);

            return response()->json([
                'message' => 'All caches cleared successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to clear caches',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function optimizeApp(): JsonResponse
    {
        try {
            Artisan::call('optimize');

            Log::info('Admin optimized application', ['user_id' => auth()->id()]);

            return response()->json([
                'message' => 'Application optimized successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Optimization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(Request $request): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!file_exists($logFile)) {
            return response()->json([
                'data' => [],
                'message' => 'No log file found',
            ]);
        }

        $lines = $request->input('lines', 100);
        $content = $this->tailFile($logFile, $lines);

        return response()->json([
            'data' => [
                'content' => $content,
                'file_size' => filesize($logFile),
                'last_modified' => date('Y-m-d H:i:s', filemtime($logFile)),
            ],
        ]);
    }

    public function clearLogs(): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            Log::info('Admin cleared log file', ['user_id' => auth()->id()]);
        }

        return response()->json([
            'message' => 'Logs cleared successfully',
        ]);
    }

    public function maintenanceMode(Request $request): JsonResponse
    {
        $action = $request->input('action'); // 'up' or 'down'

        if ($action === 'down') {
            $secret = $request->input('secret', bin2hex(random_bytes(16)));
            Artisan::call('down', ['--secret' => $secret]);
            
            return response()->json([
                'message' => 'Maintenance mode enabled',
                'secret' => $secret,
                'bypass_url' => url("/{$secret}"),
            ]);
        }

        Artisan::call('up');
        
        return response()->json([
            'message' => 'Application is now live',
        ]);
    }

    public function queueStatus(): JsonResponse
    {
        $driver = config('queue.default');
        $stats = [
            'driver' => $driver,
            'connection' => config("queue.connections.{$driver}"),
        ];

        // Get pending jobs count if using database driver
        if ($driver === 'database') {
            $stats['pending_jobs'] = DB::table('jobs')->count();
            $stats['failed_jobs'] = DB::table('failed_jobs')->count();
        }

        return response()->json([
            'data' => $stats,
        ]);
    }

    public function retryFailedJobs(): JsonResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);
            
            return response()->json([
                'message' => 'All failed jobs queued for retry',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function getDatabaseVersion(): string
    {
        try {
            $driver = config('database.default');
            if ($driver === 'mysql') {
                return DB::select('SELECT VERSION() as version')[0]->version;
            } elseif ($driver === 'pgsql') {
                return DB::select('SELECT version()')[0]->version;
            } elseif ($driver === 'sqlite') {
                return DB::select('SELECT sqlite_version() as version')[0]->version;
            }
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    protected function tailFile(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        $content = '';
        while (!$file->eof()) {
            $content .= $file->fgets();
        }
        
        return $content;
    }
}
