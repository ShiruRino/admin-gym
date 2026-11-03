<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MemberController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    });

    // Protected member routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::prefix('member')->group(function () {
            Route::get('profile', [MemberController::class, 'profile']);
            Route::put('profile', [MemberController::class, 'updateProfile']);
            Route::post('photo', [MemberController::class, 'updatePhoto']);
            Route::delete('photo', [MemberController::class, 'deletePhoto']);
            Route::put('password', [MemberController::class, 'changePassword']);
            Route::get('card', [MemberController::class, 'card']);
            Route::get('subscription', [MemberController::class, 'subscription']);
            Route::get('pt-sessions', [MemberController::class, 'ptSessions']);
            Route::get('attendances', [MemberController::class, 'attendances']);
            Route::get('invoices', [MemberController::class, 'invoices']);
        });
    });
});
