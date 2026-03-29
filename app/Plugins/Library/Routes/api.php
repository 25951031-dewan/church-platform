<?php

use App\Plugins\Library\Controllers\BookController;
use App\Plugins\Library\Controllers\BookCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('books', [BookController::class, 'index']);
Route::get('books/{book}', [BookController::class, 'show']);
Route::post('books', [BookController::class, 'store']);
Route::put('books/{book}', [BookController::class, 'update']);
Route::delete('books/{book}', [BookController::class, 'destroy']);

Route::post('books/{book}/download', [BookController::class, 'trackDownload']);

Route::get('book-categories', [BookCategoryController::class, 'index']);
Route::post('book-categories', [BookCategoryController::class, 'store']);
Route::put('book-categories/{bookCategory}', [BookCategoryController::class, 'update']);
Route::delete('book-categories/{bookCategory}', [BookCategoryController::class, 'destroy']);
