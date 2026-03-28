<?php

use App\Plugins\Events\Controllers\EventController;
use App\Plugins\Events\Controllers\EventRsvpController;
use Illuminate\Support\Facades\Route;

// Event CRUD
Route::get('events', [EventController::class, 'index']);
Route::get('events/{event}', [EventController::class, 'show']);

Route::middleware('permission:events.create')->group(function () {
    Route::post('events', [EventController::class, 'store']);
});

Route::put('events/{event}', [EventController::class, 'update']);
Route::delete('events/{event}', [EventController::class, 'destroy']);
Route::patch('events/{event}/feature', [EventController::class, 'feature']);

// RSVP
Route::post('events/{event}/rsvp', [EventRsvpController::class, 'rsvp']);
Route::delete('events/{event}/rsvp', [EventRsvpController::class, 'cancel']);
Route::get('events/{event}/attendees', [EventRsvpController::class, 'attendees']);
