<?php

use App\Plugins\Marketplace\Controllers\CategoryController;
use App\Plugins\Marketplace\Controllers\FavoriteController;
use App\Plugins\Marketplace\Controllers\ListingController;
use App\Plugins\Marketplace\Controllers\OfferController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('marketplace/categories', [CategoryController::class, 'index']);
Route::get('marketplace/categories/{category}', [CategoryController::class, 'show']);
Route::get('marketplace/listings', [ListingController::class, 'index']);
Route::get('marketplace/listings/{listing}', [ListingController::class, 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Listings CRUD
    Route::post('marketplace/listings', [ListingController::class, 'store']);
    Route::put('marketplace/listings/{listing}', [ListingController::class, 'update']);
    Route::delete('marketplace/listings/{listing}', [ListingController::class, 'destroy']);
    Route::post('marketplace/listings/{listing}/sold', [ListingController::class, 'markSold']);
    Route::get('marketplace/my-listings', [ListingController::class, 'myListings']);

    // Offers
    Route::post('marketplace/listings/{listing}/offers', [OfferController::class, 'store']);
    Route::post('marketplace/offers/{offer}/accept', [OfferController::class, 'accept']);
    Route::post('marketplace/offers/{offer}/reject', [OfferController::class, 'reject']);
    Route::post('marketplace/offers/{offer}/counter', [OfferController::class, 'counter']);
    Route::get('marketplace/my-offers', [OfferController::class, 'myOffers']);
    Route::get('marketplace/received-offers', [OfferController::class, 'receivedOffers']);

    // Favorites
    Route::post('marketplace/listings/{listing}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('marketplace/favorites', [FavoriteController::class, 'index']);
});
