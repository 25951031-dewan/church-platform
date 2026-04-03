<?php

use App\Modules\Counseling\Controllers\CounselingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/counseling')->group(function () {
    Route::post('/request', [CounselingController::class, 'request']);
    Route::get('/my-threads', [CounselingController::class, 'myThreads']);
    Route::get('/assigned', [CounselingController::class, 'assignedThreads']);
    Route::get('/threads/{thread}', [CounselingController::class, 'show']);
    Route::post('/threads/{thread}/messages', [CounselingController::class, 'sendMessage']);
    Route::put('/threads/{thread}/assign', [CounselingController::class, 'assign']);
    Route::put('/threads/{thread}/status', [CounselingController::class, 'updateStatus']);

    // Admin
    Route::get('/admin/threads', [CounselingController::class, 'allThreads']);
});
