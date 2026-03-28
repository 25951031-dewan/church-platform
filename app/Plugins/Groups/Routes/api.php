<?php

use App\Plugins\Groups\Controllers\GroupController;
use App\Plugins\Groups\Controllers\GroupMemberController;
use Illuminate\Support\Facades\Route;

// Group CRUD
Route::get('groups', [GroupController::class, 'index']);
Route::get('groups/{group}', [GroupController::class, 'show']);

Route::middleware('permission:groups.create')->group(function () {
    Route::post('groups', [GroupController::class, 'store']);
});

Route::put('groups/{group}', [GroupController::class, 'update']);
Route::delete('groups/{group}', [GroupController::class, 'destroy']);
Route::patch('groups/{group}/feature', [GroupController::class, 'feature']);

// Group membership
Route::get('groups/{group}/members', [GroupMemberController::class, 'index']);
Route::post('groups/{group}/join', [GroupMemberController::class, 'join']);
Route::delete('groups/{group}/leave', [GroupMemberController::class, 'leave']);
Route::patch('groups/{group}/members/{member}/approve', [GroupMemberController::class, 'approve']);
Route::delete('groups/{group}/members/{member}/reject', [GroupMemberController::class, 'reject']);
Route::patch('groups/{group}/members/{member}/role', [GroupMemberController::class, 'changeRole']);
Route::delete('groups/{group}/members/{member}', [GroupMemberController::class, 'remove']);
