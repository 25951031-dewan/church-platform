<?php

use App\Plugins\Blog\Controllers\ArticleController;
use App\Plugins\Blog\Controllers\ArticleCategoryController;
use App\Plugins\Blog\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Authenticated routes (this file is loaded inside auth:sanctum group in routes/api.php)
Route::post('articles', [ArticleController::class, 'store']);
Route::put('articles/{article}', [ArticleController::class, 'update']);
Route::delete('articles/{article}', [ArticleController::class, 'destroy']);

Route::post('article-categories', [ArticleCategoryController::class, 'store']);
Route::put('article-categories/{articleCategory}', [ArticleCategoryController::class, 'update']);
Route::delete('article-categories/{articleCategory}', [ArticleCategoryController::class, 'destroy']);

Route::post('tags', [TagController::class, 'store']);
Route::delete('tags/{tag}', [TagController::class, 'destroy']);
