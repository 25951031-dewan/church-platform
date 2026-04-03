<?php

use App\Plugins\LiveMeeting\Controllers\MeetingController;
use App\Plugins\LiveMeeting\Controllers\Admin\MeetingController as AdminMeetingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('meetings', [MeetingController::class, 'index']);
    Route::get('meetings/live', [MeetingController::class, 'live']);
    Route::get('meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('meetings', [MeetingController::class, 'store']);
    Route::put('meetings/{meeting}', [MeetingController::class, 'update']);
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy']);
    Route::post('meetings/{meeting}/register', [MeetingController::class, 'register']);
    Route::delete('meetings/{meeting}/register', [MeetingController::class, 'unregister']);
    Route::post('meetings/{meeting}/check-in', [MeetingController::class, 'checkIn']);
});

Route::prefix('admin')->middleware(['auth:sanctum', 'permission:admin.access'])->group(function () {
    Route::get('meetings', [AdminMeetingController::class, 'index']);
    Route::post('meetings', [AdminMeetingController::class, 'store']);
    Route::get('meetings/{meeting}', [AdminMeetingController::class, 'show']);
    Route::put('meetings/{meeting}', [AdminMeetingController::class, 'update']);
    Route::delete('meetings/{meeting}', [AdminMeetingController::class, 'destroy']);
    Route::get('meetings/{meeting}/stats', [AdminMeetingController::class, 'stats']);
});
