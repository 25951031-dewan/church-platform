<?php

use App\Plugins\Sermons\Controllers\SermonController;
use App\Plugins\Sermons\Controllers\SermonSeriesController;
use App\Plugins\Sermons\Controllers\SpeakerController;
use Illuminate\Support\Facades\Route;

// Sermon CRUD
Route::get('sermons', [SermonController::class, 'index']);
Route::get('sermons/{sermon}', [SermonController::class, 'show']);

Route::middleware('permission:sermons.create')->group(function () {
    Route::post('sermons', [SermonController::class, 'store']);
});

Route::put('sermons/{sermon}', [SermonController::class, 'update']);
Route::delete('sermons/{sermon}', [SermonController::class, 'destroy']);

// Sermon Series
Route::get('sermon-series', [SermonSeriesController::class, 'index']);
Route::get('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'show']);
Route::post('sermon-series', [SermonSeriesController::class, 'store']);
Route::put('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'update']);
Route::delete('sermon-series/{sermonSeries}', [SermonSeriesController::class, 'destroy']);

// Speakers
Route::get('speakers', [SpeakerController::class, 'index']);
Route::get('speakers/{speaker}', [SpeakerController::class, 'show']);
Route::post('speakers', [SpeakerController::class, 'store']);
Route::put('speakers/{speaker}', [SpeakerController::class, 'update']);
Route::delete('speakers/{speaker}', [SpeakerController::class, 'destroy']);
