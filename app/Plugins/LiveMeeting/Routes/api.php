<?php

use App\Plugins\LiveMeeting\Controllers\MeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('meetings', [MeetingController::class, 'index']);
    Route::get('meetings/live', [MeetingController::class, 'live']);
    Route::get('meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('meetings', [MeetingController::class, 'store']);
    Route::put('meetings/{meeting}', [MeetingController::class, 'update']);
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy']);
});
