<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Merchants\GetMerchantController;
use App\Http\Controllers\Merchants\GetMerchantsController;
use App\Http\Controllers\MerchantAuth\AcceptMerchantInvitationController;
use App\Http\Controllers\MerchantAuth\ForgotMerchantPasswordController;
use App\Http\Controllers\MerchantAuth\LoginMerchantUserController;
use App\Http\Controllers\MerchantAuth\LogoutMerchantUserController;
use App\Http\Controllers\MerchantAuth\ResetMerchantPasswordController;

// Customer facing - Browse and discover merchants
Route::prefix('merchants')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', GetMerchantsController::class);
    Route::get('/{id}', GetMerchantController::class);
});

// Merchant auth - Public endpoints
Route::prefix('merchant-auth')->group(function () {
    Route::post('/login', LoginMerchantUserController::class);
    Route::post('/forgot-password', ForgotMerchantPasswordController::class);

    Route::middleware('auth:merchant_user')->group(function () {
        Route::post('/logout', LogoutMerchantUserController::class);
    });

    Route::middleware('signed')->group(function () {
        Route::post('/invitation/{id}/{hash}', AcceptMerchantInvitationController::class)->name('merchant.invitation.setup');
        Route::post('/password/reset/{id}', ResetMerchantPasswordController::class)->name('merchant.password.reset');
    });
});

// Merchant owned - Merchant portal endpoints
Route::prefix('merchant')->middleware(['auth:merchant_user'])->group(function () {
});
