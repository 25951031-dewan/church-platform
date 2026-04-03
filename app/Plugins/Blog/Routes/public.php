<?php

use App\Plugins\Blog\Controllers\ArticleController;
use App\Plugins\Blog\Controllers\ArticleCategoryController;
use App\Plugins\Blog\Controllers\TagController;
use Illuminate\Support\Facades\Route;

// Public routes (no auth required — articles are public for SEO)
Route::get('articles', [ArticleController::class, 'index']);
Route::get('articles/{article}', [ArticleController::class, 'show']);
Route::get('article-categories', [ArticleCategoryController::class, 'index']);
Route::get('tags', [TagController::class, 'index']);
