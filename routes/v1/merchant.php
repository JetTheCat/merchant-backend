<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Merchants\GetMerchantController;
use App\Http\Controllers\Merchants\GetMerchantsController;
use App\Http\Controllers\MerchantAuth\AcceptMerchantInvitationController;

// Customer facing - Browse and discover merchants
Route::prefix('merchants')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', GetMerchantsController::class);
    Route::get('/{id}', GetMerchantController::class);
});

// Merchant auth - Invitation setup
Route::prefix('merchant-auth')->middleware(['signed'])->group(function () {
    Route::post('/invitation/{id}/{hash}', AcceptMerchantInvitationController::class)->name('merchant.invitation.setup');
});

// Merchant owned - Merchant portal endpoints
Route::prefix('merchant')->middleware(['auth:sanctum'])->group(function () {
});
