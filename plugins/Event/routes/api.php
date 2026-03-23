<?php
use Illuminate\Support\Facades\Route;
use Plugins\Event\Controllers\EventController;
use Plugins\Event\Controllers\EventPostController;
use Plugins\Event\Controllers\EventRsvpController;

Route::prefix('v1')->name('api.v1.events.')->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('index');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('show');
    Route::get('/events/{id}/attendees', [EventController::class, 'attendees'])->name('attendees');
    Route::get('/events/{id}/posts', [EventPostController::class, 'index'])->name('posts.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/events', [EventController::class, 'store'])->name('store');
        Route::patch('/events/{id}', [EventController::class, 'update'])->name('update');
        Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('destroy');
        Route::post('/events/{id}/rsvp', [EventRsvpController::class, 'update'])->name('rsvp.update');
        Route::delete('/events/{id}/rsvp', [EventRsvpController::class, 'destroy'])->name('rsvp.destroy');
        Route::post('/events/{id}/posts', [EventPostController::class, 'store'])->name('posts.store');
    });
});
