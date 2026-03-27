<?php

use App\Http\Controllers\Api\Admin\AdminCommunityController;
use App\Http\Controllers\Api\Admin\AdminEventController;
use App\Http\Controllers\Api\Admin\AdminPostController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\PageBuilderController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SduiController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Auth
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user'])->name('auth.user');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
    // Server-Driven UI endpoints (public)
    Route::prefix('sdui')->name('sdui.')->group(function () {
        Route::get('home', [SduiController::class, 'home'])->name('home');
        Route::get('church/{id}', [SduiController::class, 'church'])->name('church');
    });
    // Public captcha config (site key only — secret never exposed)
    Route::get('captcha/config', function () {
        $row = DB::table('settings')->where('key', 'captcha')->first();

        return response()->json([
            'captcha_enabled' => (bool) ($row?->captcha_enabled ?? false),
            'turnstile_site_key' => $row?->turnstile_site_key ?? null,
        ]);
    })->name('captcha.config');
    // Profile routes
    Route::get('users/{id}', [UserProfileController::class, 'show']);
    Route::patch('profile', [UserProfileController::class, 'update'])->middleware('auth:sanctum');

    // Admin routes (auth:sanctum + admin role required)
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
        Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');

        // Pages + page builder
        Route::get('pages', [PageBuilderController::class, 'index'])->name('pages.index');
        Route::post('pages', [PageBuilderController::class, 'store'])->name('pages.store');
        Route::patch('pages/{page}', [PageBuilderController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageBuilderController::class, 'destroy'])->name('pages.destroy');
        Route::get('pages/{page}/builder', [PageBuilderController::class, 'getBuilder'])->name('pages.builder.get');
        Route::put('pages/{page}/builder', [PageBuilderController::class, 'saveBuilder'])->name('pages.builder.save');

        // Users management
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Posts moderation
        Route::get('posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::delete('posts/{post}', [AdminPostController::class, 'destroy'])->name('posts.destroy');
        Route::patch('posts/{post}/moderate', [AdminPostController::class, 'moderate'])->name('posts.moderate');

        // Events management
        Route::get('events', [AdminEventController::class, 'index'])->name('events.index');
        Route::delete('events/{event}', [AdminEventController::class, 'destroy'])->name('events.destroy');

        // Communities management
        Route::get('communities', [AdminCommunityController::class, 'index'])->name('communities.index');
        Route::delete('communities/{community}', [AdminCommunityController::class, 'destroy'])->name('communities.destroy');
    });
});
