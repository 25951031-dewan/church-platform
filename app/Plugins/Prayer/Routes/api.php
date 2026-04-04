<?php

use App\Plugins\Prayer\Controllers\PrayerRequestController;
use App\Plugins\Prayer\Controllers\PrayerUpdateController;
use Illuminate\Support\Facades\Route;

// Authenticated routes (all actions require login)
Route::get('prayer-requests', [PrayerRequestController::class, 'index']);
Route::get('prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'show']);
Route::post('prayer-requests', [PrayerRequestController::class, 'store']);
Route::put('prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'update']);
Route::delete('prayer-requests/{prayerRequest}', [PrayerRequestController::class, 'destroy']);

// Moderation
Route::patch('prayer-requests/{prayerRequest}/moderate', [PrayerRequestController::class, 'moderate']);
Route::patch('prayer-requests/{prayerRequest}/flag', [PrayerRequestController::class, 'toggleFlag']);

// Prayer updates (progress notes)
Route::get('prayer-requests/{prayerRequest}/updates', [PrayerUpdateController::class, 'index']);
Route::post('prayer-requests/{prayerRequest}/updates', [PrayerUpdateController::class, 'store']);
Route::delete('prayer-requests/{prayerRequest}/updates/{prayerUpdate}', [PrayerUpdateController::class, 'destroy']);
