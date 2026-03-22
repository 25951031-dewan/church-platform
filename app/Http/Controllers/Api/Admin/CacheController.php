<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    /**
     * Clear all application caches.
     *
     * Flushes tagged caches (settings, theme, menu) and runs artisan cache:clear.
     *
     * @group Admin / Cache
     *
     * @response 200 {"message": "Cache cleared successfully."}
     */
    public function clear(): JsonResponse
    {
        foreach (['settings', 'theme', 'menu'] as $tag) {
            try {
                Cache::tags([$tag])->flush();
            } catch (\BadMethodCallException) {
                // Driver doesn't support tags (file/database) — fall back to full flush
                break;
            }
        }

        Artisan::call('cache:clear');

        return response()->json(['message' => 'Cache cleared successfully.']);
    }
}
