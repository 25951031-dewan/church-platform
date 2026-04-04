<?php

use App\Plugins\Timeline\Controllers\Admin\TimelineSettingsController;
use Illuminate\Support\Facades\Route;

// Timeline Admin Settings Routes
Route::middleware(['auth', 'admin'])->prefix('admin/timeline')->name('admin.timeline.')->group(function () {
    
    // Community Settings
    Route::get('/settings/community', [TimelineSettingsController::class, 'getCommunitySettings'])->name('settings.community.get');
    Route::post('/settings/community', [TimelineSettingsController::class, 'updateCommunitySettings'])->name('settings.community.update');
    
    // Media Settings
    Route::get('/settings/media', [TimelineSettingsController::class, 'getMediaSettings'])->name('settings.media.get');
    Route::post('/settings/media', [TimelineSettingsController::class, 'updateMediaSettings'])->name('settings.media.update');
    
    // Daily Verse Settings
    Route::get('/settings/daily-verse', [TimelineSettingsController::class, 'getDailyVerseSettings'])->name('settings.verse.get');
    Route::post('/settings/daily-verse', [TimelineSettingsController::class, 'updateDailyVerseSettings'])->name('settings.verse.update');
    
    // Daily Verse CSV Management
    Route::post('/daily-verses/import', [TimelineSettingsController::class, 'importDailyVerses'])->name('verse.import');
    Route::get('/daily-verses/export', [TimelineSettingsController::class, 'exportDailyVerses'])->name('verse.export');
    Route::get('/daily-verses/sample', [TimelineSettingsController::class, 'downloadSampleCsv'])->name('verse.sample');
});