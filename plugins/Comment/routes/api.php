<?php
use Illuminate\Support\Facades\Route;
use Plugins\Comment\Controllers\CommentController;

Route::prefix('v1')->group(function () {
    Route::get('posts/{postId}/comments',  [CommentController::class, 'index']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('comments',            [CommentController::class, 'store']);
        Route::delete('comments/{id}',     [CommentController::class, 'destroy']);
    });
});
