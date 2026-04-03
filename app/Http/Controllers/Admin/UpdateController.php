<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;

class UpdateController extends Controller
{
    /**
     * Get current version info and pending migration count.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'version' => config('version.version'),
            'release_date' => config('version.release_date'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'installed_at' => File::exists(storage_path('installed'))
                ? File::get(storage_path('installed'))
                : null,
        ]);
    }

    /**
     * Upload and extract an update package (zip file).
     * The zip must contain files relative to the application root.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'update_package' => 'required|file|mimes:zip|max:102400', // 100MB max
        ]);

        $zip = new ZipArchive();
        $file = $request->file('update_package');

        if ($zip->open($file->getPathname()) !== true) {
            return response()->json(['message' => 'Could not open zip file.'], 422);
        }

        // Verify the zip has expected structure (contains artisan or app/ directory)
        $hasExpectedStructure = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'app/') || $name === 'artisan') {
                $hasExpectedStructure = true;
                break;
            }
        }

        if (!$hasExpectedStructure) {
            $zip->close();
            return response()->json(['message' => 'Invalid update package structure.'], 422);
        }

        // Extract to application root (overwrites existing files)
        $zip->extractTo(base_path());
        $zip->close();

        return response()->json(['message' => 'Package extracted successfully. Run migrations to complete the update.']);
    }

    /**
     * Run pending database migrations.
     */
    public function migrate(): JsonResponse
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'message' => 'Migrations completed.',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Migration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Clear all application caches.
     */
    public function clearCaches(): JsonResponse
    {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        return response()->json(['message' => 'All caches cleared.']);
    }
}
