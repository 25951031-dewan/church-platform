<?php

use Illuminate\Support\Facades\Route;
use Plugins\Installer\Controllers\UpdaterController;

Route::get('/', [UpdaterController::class, 'dashboard'])->name('update.dashboard');
// GET (not POST) — EventSource browser API only supports GET requests for SSE streams.
// The 'signed' middleware verifies a Laravel signed URL, providing CSRF-equivalent protection.
Route::get('/run', [UpdaterController::class, 'run'])->name('update.run')->middleware('signed');
