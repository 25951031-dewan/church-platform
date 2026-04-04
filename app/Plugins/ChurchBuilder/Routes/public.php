<?php

use App\Plugins\ChurchBuilder\Controllers\PublicChurchController;
use Illuminate\Support\Facades\Route;

// Public API endpoints (no authentication required)
Route::get('/churches/public', [PublicChurchController::class, 'apiIndex']);
Route::get('/churches/public/{slug}', [PublicChurchController::class, 'apiShow']);