<?php

use App\Http\Controllers\Api\Admin\PageBuilderController;
use App\Http\Controllers\Api\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Admin routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
        Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Pages + page builder
        Route::get('pages', [PageBuilderController::class, 'index'])->name('pages.index');
        Route::post('pages', [PageBuilderController::class, 'store'])->name('pages.store');
        Route::patch('pages/{page}', [PageBuilderController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageBuilderController::class, 'destroy'])->name('pages.destroy');
        Route::get('pages/{page}/builder', [PageBuilderController::class, 'getBuilder'])->name('pages.builder.get');
        Route::put('pages/{page}/builder', [PageBuilderController::class, 'saveBuilder'])->name('pages.builder.save');
    });
});
