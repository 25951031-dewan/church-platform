<?php

use Illuminate\Support\Facades\Route;
use Plugins\Analytics\Controllers\AnalyticsDashboardController;

Route::prefix('v1/admin')->name('api.v1.admin.analytics.')->group(function () {
    Route::get('analytics', [AnalyticsDashboardController::class, 'index'])->name('index');
});
