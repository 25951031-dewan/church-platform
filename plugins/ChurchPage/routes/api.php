<?php

use Illuminate\Support\Facades\Route;
use Plugins\ChurchPage\Controllers\ChurchPageController;

Route::prefix('v1')->name('api.v1.churches.')->group(function () {
    Route::get('/churches', [ChurchPageController::class, 'index'])->name('index');
    Route::get('/churches/{slug}', [ChurchPageController::class, 'show'])->name('show');
    Route::get('/churches/{church}/members', [ChurchPageController::class, 'members'])->name('members');
});
