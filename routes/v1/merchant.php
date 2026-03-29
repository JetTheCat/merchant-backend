<?php

declare(strict_types=1);

use App\Http\Controllers\MerchantAuth\AcceptMerchantInvitationController;
use App\Http\Controllers\MerchantAuth\ForgotMerchantPasswordController;
use App\Http\Controllers\MerchantAuth\LoginMerchantUserController;
use App\Http\Controllers\MerchantAuth\LogoutMerchantUserController;
use App\Http\Controllers\MerchantAuth\ResetMerchantPasswordController;
use App\Http\Controllers\MerchantPortal\GetMerchantProfileController;
use App\Http\Controllers\MerchantPortal\ListMerchantUsersController;
use App\Http\Controllers\MerchantPortal\RemoveMerchantUserController;
use App\Http\Controllers\MerchantPortal\UpdateMerchantProfileController;
use App\Http\Controllers\MerchantPortal\UpdateMerchantUserController;
use App\Http\Controllers\Merchants\GetMerchantController;
use App\Http\Controllers\Merchants\GetMerchantsController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/', GetMerchantProfileController::class);
    Route::patch('/', UpdateMerchantProfileController::class);

    Route::prefix('users')->group(function () {
        Route::get('/', ListMerchantUsersController::class);
        Route::patch('/{id}', UpdateMerchantUserController::class);
        Route::delete('/{id}', RemoveMerchantUserController::class);
    });
});
