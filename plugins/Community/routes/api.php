<?php

use Illuminate\Support\Facades\Route;
use Plugins\Community\Controllers\CounselGroupController;

Route::prefix('v1')->name('api.v1.community.')->group(function () {
    Route::get('/counsel-groups', [CounselGroupController::class, 'index'])->name('counsel-groups.index');
    Route::post('/counsel-groups', [CounselGroupController::class, 'store'])->name('counsel-groups.store');
    Route::post('/counsel-groups/{counselGroup}/request-join', [CounselGroupController::class, 'requestJoin'])->name('counsel-groups.request-join');
    Route::post('/counsel-groups/{counselGroup}/approve/{userId}', [CounselGroupController::class, 'approveUser'])->name('counsel-groups.approve');
});
