<?php

use Illuminate\Support\Facades\Route;
use Plugins\Faq\Controllers\FaqAdminController;
use Plugins\Faq\Controllers\FaqController;

Route::prefix('v1')->name('api.v1.faq.')->group(function () {
    // Public
    Route::get('/faq', [FaqController::class, 'index'])->name('index');
    Route::get('/faq/{id}', [FaqController::class, 'show'])->name('show');

    // Admin CRUD
    Route::prefix('admin/faq')->name('admin.')->group(function () {
        Route::get('categories', [FaqAdminController::class, 'indexCategories'])->name('categories.index');
        Route::post('categories', [FaqAdminController::class, 'storeCategory'])->name('categories.store');
        Route::patch('categories/{category}', [FaqAdminController::class, 'updateCategory'])->name('categories.update');
        Route::delete('categories/{category}', [FaqAdminController::class, 'destroyCategory'])->name('categories.destroy');

        Route::get('faqs', [FaqAdminController::class, 'indexFaqs'])->name('faqs.index');
        Route::post('faqs', [FaqAdminController::class, 'storeFaq'])->name('faqs.store');
        Route::patch('faqs/{faq}', [FaqAdminController::class, 'updateFaq'])->name('faqs.update');
        Route::delete('faqs/{faq}', [FaqAdminController::class, 'destroyFaq'])->name('faqs.destroy');
    });
});
