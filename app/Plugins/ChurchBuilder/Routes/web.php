<?php

use App\Plugins\ChurchBuilder\Controllers\PublicChurchController;
use Illuminate\Support\Facades\Route;

// Public church directory and individual church pages
Route::get('/churches', [PublicChurchController::class, 'index'])->name('churches.index');
Route::get('/church/{slug}', [PublicChurchController::class, 'show'])->name('church.show');
Route::get('/church/{slug}/{page}', [PublicChurchController::class, 'showPage'])->name('church.page');