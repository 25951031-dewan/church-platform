<?php

use Illuminate\Support\Facades\Route;
use Plugins\Entity\Controllers\PageController;
use Plugins\Entity\Controllers\PageFollowController;
use Plugins\Entity\Controllers\PageMemberController;

Route::prefix('v1')->name('api.v1.pages.')->group(function () {
    // Public
    Route::get('/pages', [PageController::class, 'index'])->name('index');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('show');
    Route::get('/pages/{id}/members', [PageMemberController::class, 'index'])->name('members.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/pages', [PageController::class, 'store'])->name('store');
        Route::put('/pages/{id}', [PageController::class, 'update'])->name('update');
        Route::delete('/pages/{id}', [PageController::class, 'destroy'])->name('destroy');

        Route::post('/pages/{id}/follow', [PageFollowController::class, 'store'])->name('follow.store');
        Route::delete('/pages/{id}/follow', [PageFollowController::class, 'destroy'])->name('follow.destroy');

        Route::put('/pages/{id}/members/{userId}/role', [PageMemberController::class, 'updateRole'])->name('members.role');
        Route::delete('/pages/{id}/members/{userId}', [PageMemberController::class, 'destroy'])->name('members.destroy');
    });
});
