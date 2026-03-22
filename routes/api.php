<?php

use App\Http\Controllers\Api\Admin\CacheController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\PageBuilderController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Admin\SubSettingsController;
use App\Http\Controllers\Api\SduiController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Server-Driven UI endpoints (public)
    Route::prefix('sdui')->name('sdui.')->group(function () {
        Route::get('home', [SduiController::class, 'home'])->name('home');
        Route::get('church/{id}', [SduiController::class, 'church'])->name('church');
    });
    // Public captcha config (site key only — secret never exposed)
    Route::get('captcha/config', function () {
        $row = DB::table('settings')->where('key', 'captcha')->first();
        return response()->json([
            'captcha_enabled'    => (bool) ($row?->captcha_enabled ?? false),
            'turnstile_site_key' => $row?->turnstile_site_key ?? null,
        ]);
    })->name('captcha.config');
    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Platform settings (mode, directory, feature toggles)
        Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
        Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Sub-settings by group: general | email | appearance | seo | cache | auth | storage | api
        Route::get('settings/{group}', [SubSettingsController::class, 'show'])->name('settings.group.show');
        Route::patch('settings/{group}', [SubSettingsController::class, 'update'])->name('settings.group.update');

        // Cache management
        Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

        // Media manager
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::post('media', [MediaController::class, 'store'])->name('media.store');
        Route::post('media/bulk-delete', [MediaController::class, 'bulkDelete'])->name('media.bulk-delete');

        // Pages + page builder
        Route::get('pages', [PageBuilderController::class, 'index'])->name('pages.index');
        Route::post('pages', [PageBuilderController::class, 'store'])->name('pages.store');
        Route::patch('pages/{page}', [PageBuilderController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageBuilderController::class, 'destroy'])->name('pages.destroy');
        Route::get('pages/{page}/builder', [PageBuilderController::class, 'getBuilder'])->name('pages.builder.get');
        Route::put('pages/{page}/builder', [PageBuilderController::class, 'saveBuilder'])->name('pages.builder.save');
    });
});
