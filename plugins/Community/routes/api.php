<?php

use Illuminate\Support\Facades\Route;
use Plugins\Community\Controllers\CommunityController;
use Plugins\Community\Controllers\CommunityMemberController;
use Plugins\Community\Controllers\CounselGroupController;

Route::prefix('v1')->name('api.v1.community.')->group(function () {
    Route::get('/counsel-groups', [CounselGroupController::class, 'index'])->name('counsel-groups.index');
    Route::post('/counsel-groups', [CounselGroupController::class, 'store'])->name('counsel-groups.store');
    Route::post('/counsel-groups/{counselGroup}/request-join', [CounselGroupController::class, 'requestJoin'])->name('counsel-groups.request-join');
    Route::post('/counsel-groups/{counselGroup}/approve/{userId}', [CounselGroupController::class, 'approveUser'])->name('counsel-groups.approve');

    Route::get('communities',       [CommunityController::class, 'index']);
    Route::get('communities/{id}',  [CommunityController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('communities',               [CommunityController::class, 'store']);
        Route::post('communities/{id}/join',     [CommunityController::class, 'join']);
        Route::delete('communities/{id}/leave',  [CommunityController::class, 'leave']);
        Route::get('communities/{id}/members',                     [CommunityMemberController::class, 'index']);
        Route::post('communities/{id}/members/{userId}/approve',   [CommunityMemberController::class, 'approve']);
        Route::delete('communities/{id}/members/{userId}/approve', [CommunityMemberController::class, 'reject']);
        Route::patch('communities/{id}/members/{userId}',          [CommunityMemberController::class, 'updateRole']);
        Route::post('communities/{id}/members/{userId}/ban',       [CommunityMemberController::class, 'ban']);
        Route::delete('communities/{id}/members/{userId}/ban',     [CommunityMemberController::class, 'unban']);
    });
});
