<?php
use Illuminate\Support\Facades\Route;
use Plugins\Reaction\Controllers\ReactionController;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->post('reactions', [ReactionController::class, 'toggle']);
    Route::get('reactions/{type}/{id}', [ReactionController::class, 'summary']);
});
