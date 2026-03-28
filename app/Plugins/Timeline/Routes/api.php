<?php

use App\Plugins\Timeline\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{post}', [PostController::class, 'show']);

Route::middleware('permission:posts.create')->group(function () {
    Route::post('posts', [PostController::class, 'store']);
});

Route::put('posts/{post}', [PostController::class, 'update']);
Route::delete('posts/{post}', [PostController::class, 'destroy']);
Route::patch('posts/{post}/pin', [PostController::class, 'pin']);
