<?php

use Illuminate\Support\Facades\Route;
use Plugins\ChurchPage\Controllers\ChurchImportController;
use Plugins\ChurchPage\Controllers\ChurchPageController;

Route::prefix('v1')->name('api.v1.churches.')->group(function () {
    // Public church directory
    Route::get('/churches', [ChurchPageController::class, 'index'])->name('index');
    Route::get('/churches/{slug}', [ChurchPageController::class, 'show'])->name('show');
    Route::get('/churches/{church}/members', [ChurchPageController::class, 'members'])->name('members');

    // Admin: CSV import / export (auth + admin role enforced by route middleware in production)
    Route::prefix('admin/churches')->name('admin.')->group(function () {
        Route::get('export', [ChurchImportController::class, 'exportCsv'])->name('export');
        Route::post('import', [ChurchImportController::class, 'importCsv'])->name('import');
        Route::get('sample-csv', [ChurchImportController::class, 'sampleCsv'])->name('sample-csv');
    });
});
