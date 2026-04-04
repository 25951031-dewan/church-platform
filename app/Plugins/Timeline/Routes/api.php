<?php

use App\Plugins\Timeline\Controllers\PostController;
use App\Plugins\Timeline\Controllers\FeedLayoutController;
use App\Plugins\Timeline\Controllers\FeedWidgetController;
use App\Plugins\Timeline\Controllers\Admin\DailyVerseAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Timeline Plugin API Routes
|--------------------------------------------------------------------------
|
| Feed layouts system routes for customizable feed page structure
| Following BeMusic architecture patterns
|
*/

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Feed Layout Management Routes
    Route::prefix('feed-layouts')->group(function () {
        Route::get('/', [FeedLayoutController::class, 'index']);
        Route::get('/active', [FeedLayoutController::class, 'active']);
        Route::post('/', [FeedLayoutController::class, 'store']);
        Route::get('/{layout}', [FeedLayoutController::class, 'show']);
        Route::put('/{layout}', [FeedLayoutController::class, 'update']);
        Route::delete('/{layout}', [FeedLayoutController::class, 'destroy']);
    });

    // Feed Widget Management Routes
    Route::prefix('feed-widgets')->group(function () {
        Route::get('/', [FeedWidgetController::class, 'index']);
        Route::get('/categories', [FeedWidgetController::class, 'categories']);
        Route::get('/{widget}', [FeedWidgetController::class, 'show']);
        Route::post('/{widget}/validate-config', [FeedWidgetController::class, 'validateConfig']);
        Route::post('/{widget}/preview', [FeedWidgetController::class, 'preview']);
    });

    // Daily Verse Admin Routes
    Route::prefix('admin/daily-verses')->group(function () {
        Route::get('/', [DailyVerseAdminController::class, 'index']);
        Route::get('/export', [DailyVerseAdminController::class, 'export']);
        Route::post('/', [DailyVerseAdminController::class, 'store']);
        Route::post('/import', [DailyVerseAdminController::class, 'import']);
        Route::post('/auto-schedule', [DailyVerseAdminController::class, 'autoSchedule']);
        Route::get('/{verse}', [DailyVerseAdminController::class, 'show']);
        Route::put('/{verse}', [DailyVerseAdminController::class, 'update']);
        Route::delete('/{verse}', [DailyVerseAdminController::class, 'destroy']);
    });
    
});

// Legacy Timeline Post Routes (existing - some need auth, some public)
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);

Route::middleware('permission:posts.create')->group(function () {
    Route::post('posts', [PostController::class, 'store']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::put('posts/{post}', [PostController::class, 'update']);
    Route::delete('posts/{post}', [PostController::class, 'destroy']);
    Route::patch('posts/{post}/pin', [PostController::class, 'pin']);
});
