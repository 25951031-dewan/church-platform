<?php

use App\Modules\Community\Controllers\CommentController;
use App\Modules\Community\Controllers\FeedController;
use App\Modules\Community\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('api/community')->group(function () {
    // Feed
    Route::get('/feed', [FeedController::class, 'index']);
    Route::post('/posts', [FeedController::class, 'store']);
    Route::get('/posts/{communityPost}', [FeedController::class, 'show']);
    Route::put('/posts/{communityPost}', [FeedController::class, 'update']);
    Route::delete('/posts/{communityPost}', [FeedController::class, 'destroy']);
    Route::post('/posts/{communityPost}/like', [FeedController::class, 'toggleLike']);
    Route::post('/posts/{communityPost}/share', [FeedController::class, 'share']);

    // Comments
    Route::post('/posts/{communityPost}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Groups
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::put('/groups/{group}', [GroupController::class, 'update']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::get('/groups/{group}/members', [GroupController::class, 'members']);
});
