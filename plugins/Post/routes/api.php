<?php

use Illuminate\Support\Facades\Route;
use Plugins\Post\Controllers\PollVoteController;
use Plugins\Post\Controllers\PostController;
use Plugins\Post\Controllers\PrayerAnswerController;

Route::prefix('v1')->group(function () {
    Route::name('api.v1.posts.')->group(function () {
        Route::post('/posts', [PostController::class, 'store'])->name('store');
        Route::post('/posts/{id}/cross-post', [PostController::class, 'crossPost'])->name('cross-post');
    });

    // Public route — no auth required
    Route::get('/posts/{id}/votes', [PollVoteController::class, 'counts'])
        ->name('api.v1.posts.vote.counts');

    // Auth-required routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts/{id}/answer-prayer', [PrayerAnswerController::class, 'toggle'])
            ->name('api.v1.posts.answer-prayer');
        Route::post('/posts/{id}/vote', [PollVoteController::class, 'store'])
            ->name('api.v1.posts.vote.store');
        Route::delete('/posts/{id}/vote', [PollVoteController::class, 'destroy'])
            ->name('api.v1.posts.vote.destroy');
    });
});
