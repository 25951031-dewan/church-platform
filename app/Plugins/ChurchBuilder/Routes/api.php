<?php

use App\Plugins\ChurchBuilder\Controllers\ChurchMemberController;
use App\Plugins\ChurchBuilder\Controllers\ChurchPageController;
use App\Plugins\ChurchBuilder\Controllers\ChurchProfileController;
use App\Plugins\ChurchBuilder\Controllers\ChurchWebsiteController;
use Illuminate\Support\Facades\Route;

// Church profile + directory
Route::get('churches', [ChurchProfileController::class, 'index']);
Route::get('churches/{church}', [ChurchProfileController::class, 'show']);
Route::patch('churches/{church}/verify', [ChurchProfileController::class, 'verify']);
Route::patch('churches/{church}/feature', [ChurchProfileController::class, 'feature']);

// Membership
Route::post('churches/{church}/join', [ChurchMemberController::class, 'join']);
Route::delete('churches/{church}/leave', [ChurchMemberController::class, 'leave']);
Route::get('churches/{church}/members', [ChurchMemberController::class, 'members']);
Route::delete('churches/{church}/members/{userId}', [ChurchMemberController::class, 'removeMember']);
Route::patch('churches/{church}/members/{userId}/role', [ChurchMemberController::class, 'updateRole']);

// Church pages
Route::get('churches/{church}/pages', [ChurchPageController::class, 'index']);
Route::get('churches/{church}/pages/{page}', [ChurchPageController::class, 'show']);
Route::post('churches/{church}/pages', [ChurchPageController::class, 'store']);
Route::put('churches/{church}/pages/{page}', [ChurchPageController::class, 'update']);
Route::delete('churches/{church}/pages/{page}', [ChurchPageController::class, 'destroy']);

// Website Builder (Church Admin Dashboard)
Route::group(['prefix' => 'churches/{church}/website'], function () {
    Route::get('/', [ChurchWebsiteController::class, 'show']);
    Route::put('/general', [ChurchWebsiteController::class, 'updateGeneral']);
    Route::put('/about', [ChurchWebsiteController::class, 'updateAbout']);
    Route::put('/appearance', [ChurchWebsiteController::class, 'updateAppearance']);
    Route::put('/seo', [ChurchWebsiteController::class, 'updateSeo']);
    Route::post('/logo', [ChurchWebsiteController::class, 'uploadLogo']);
    Route::post('/cover', [ChurchWebsiteController::class, 'uploadCoverPhoto']);
    Route::post('/documents', [ChurchWebsiteController::class, 'uploadDocument']);
    Route::delete('/documents/{index}', [ChurchWebsiteController::class, 'deleteDocument']);
});
