<?php
use Illuminate\Support\Facades\Route;
use Plugins\Feed\Controllers\FeedController;

Route::prefix('v1')->group(function () {
    Route::get('feed',                         [FeedController::class, 'home'])->middleware('auth:sanctum');
    Route::get('feed/community/{communityId}', [FeedController::class, 'community']);
    Route::get('feed/church/{churchId}',       [FeedController::class, 'church']);
});
