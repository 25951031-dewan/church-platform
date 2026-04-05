<?php

use App\Plugins\Timeline\Controllers\PostController;
use App\Plugins\Timeline\Controllers\FeedLayoutController;
use App\Plugins\Timeline\Controllers\FeedWidgetController;
use App\Plugins\Timeline\Controllers\Admin\DailyVerseAdminController;
use App\Plugins\Timeline\Controllers\Admin\TimelineSettingsController;
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
        Route::patch('/{layout}/activate', [FeedLayoutController::class, 'activate']);
        Route::delete('/{layout}', [FeedLayoutController::class, 'destroy']);
        Route::post('/{layout}/duplicate', [FeedLayoutController::class, 'duplicate']);
    });

    // Feed Widget Management Routes
    Route::prefix('feed-widgets')->group(function () {
        Route::get('/', [FeedWidgetController::class, 'index']);
        Route::get('/categories', [FeedWidgetController::class, 'categories']);
        Route::get('/{widget}', [FeedWidgetController::class, 'show']);
        Route::post('/{widget}/validate-config', [FeedWidgetController::class, 'validateConfig']);
        Route::post('/{widget}/preview', [FeedWidgetController::class, 'preview']);
    });

    // Timeline Settings API Routes (for admin panel)
    Route::middleware('permission:admin.access')->prefix('admin/timeline')->group(function () {
        
        // Community Settings
        Route::get('/settings/community', [TimelineSettingsController::class, 'getCommunitySettings']);
        Route::put('/settings/community', [TimelineSettingsController::class, 'updateCommunitySettings']);
        
        // Media Settings  
        Route::get('/settings/media', [TimelineSettingsController::class, 'getMediaSettings']);
        Route::put('/settings/media', [TimelineSettingsController::class, 'updateMediaSettings']);
        
        // Daily Verse Settings
        Route::get('/settings/daily-verse', [TimelineSettingsController::class, 'getDailyVerseSettings']);
        Route::put('/settings/daily-verse', [TimelineSettingsController::class, 'updateDailyVerseSettings']);
        
        // Daily Verse CSV Management
        Route::post('/daily-verses/import', [TimelineSettingsController::class, 'importDailyVerses']);
        Route::get('/daily-verses/export', [TimelineSettingsController::class, 'exportDailyVerses']);
        Route::get('/daily-verses/sample', [TimelineSettingsController::class, 'downloadSampleCsv']);
    });

    // Daily Verse Admin Routes (enhanced CRUD)
    Route::prefix('admin/daily-verses')->middleware('permission:admin.access')->group(function () {
        Route::get('/', [DailyVerseAdminController::class, 'index']);
        Route::get('/export', [DailyVerseAdminController::class, 'export']);
        Route::post('/', [DailyVerseAdminController::class, 'store']);
        Route::post('/import', [DailyVerseAdminController::class, 'import']);
        Route::post('/auto-schedule', [DailyVerseAdminController::class, 'autoSchedule']);
        Route::post('/bulk-delete', [DailyVerseAdminController::class, 'bulkDelete']);
        Route::post('/bulk-update', [DailyVerseAdminController::class, 'bulkUpdate']);
        Route::get('/{verse}', [DailyVerseAdminController::class, 'show']);
        Route::put('/{verse}', [DailyVerseAdminController::class, 'update']);
        Route::patch('/{verse}/activate', [DailyVerseAdminController::class, 'activate']);
        Route::delete('/{verse}', [DailyVerseAdminController::class, 'destroy']);
    });
    
});

// Public Timeline API Routes (no auth required)
Route::prefix('timeline')->group(function () {
    Route::get('/active-layout', [FeedLayoutController::class, 'publicActive']);
    Route::get('/daily-verse', [DailyVerseAdminController::class, 'getTodaysVerse']);
    Route::get('/feed-data', [PostController::class, 'feedData']);
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
