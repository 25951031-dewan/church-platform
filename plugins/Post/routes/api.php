<?php

use Illuminate\Support\Facades\Route;
use Plugins\Post\Controllers\PostController;

Route::prefix('v1')->name('api.v1.posts.')->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->name('store');
    Route::post('/posts/{id}/cross-post', [PostController::class, 'crossPost'])->name('cross-post');
});
