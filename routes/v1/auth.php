<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendVerificationEmailController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', RegisterController::class);
    Route::post('/login', LoginController::class)->middleware('throttle:login');
    Route::post('/forgot-password', ForgotPasswordController::class)->middleware('throttle:forgot-password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', LogoutController::class);
        Route::post('/email-verification/resend', ResendVerificationEmailController::class)
            ->middleware('throttle:resend-verification')
            ->name('verification.send');
    });

    Route::middleware('signed')->group(function () {
        Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)->name('verification.verify');
        Route::post('/password/reset/{id}', ResetPasswordController::class)->name('password.reset');
    });
});
