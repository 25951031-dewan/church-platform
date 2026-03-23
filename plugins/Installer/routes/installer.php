<?php

use Illuminate\Support\Facades\Route;
use Plugins\Installer\Controllers\InstallerController;

Route::get('/', fn () => redirect('/install/step1'));
Route::get('/step1', [InstallerController::class, 'step1']);
Route::post('/step1', [InstallerController::class, 'postStep1']);
Route::get('/step2', [InstallerController::class, 'step2']);
Route::post('/step2', [InstallerController::class, 'postStep2']);
Route::get('/step3', [InstallerController::class, 'step3']);
Route::post('/step3', [InstallerController::class, 'postStep3']);
Route::get('/complete', [InstallerController::class, 'complete']);
