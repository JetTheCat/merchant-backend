<?php

declare(strict_types=1);

use App\Http\Controllers\MerchantAuth\AcceptMerchantInvitationController;
use App\Http\Controllers\MerchantAuth\ForgotMerchantPasswordController;
use App\Http\Controllers\MerchantAuth\LoginMerchantUserController;
use App\Http\Controllers\MerchantAuth\LogoutMerchantUserController;
use App\Http\Controllers\MerchantAuth\ResetMerchantPasswordController;
use App\Http\Controllers\MerchantPortal\CreateCreditPackageController;
use App\Http\Controllers\MerchantPortal\CreateMerchantStaffController;
use App\Http\Controllers\MerchantPortal\DeleteCreditPackageController;
use App\Http\Controllers\MerchantPortal\GenerateRedemptionQrController;
use App\Http\Controllers\MerchantPortal\GetAuthenticatedMerchantUserController;
use App\Http\Controllers\MerchantPortal\GetMerchantProfileController;
use App\Http\Controllers\MerchantPortal\GetMerchantStatsController;
use App\Http\Controllers\MerchantPortal\GetSettlementController;
use App\Http\Controllers\MerchantPortal\GetSettlementInvoiceController;
use App\Http\Controllers\MerchantPortal\GetTodayRedemptionSummaryController;
use App\Http\Controllers\MerchantPortal\ListCreditPackagesController;
use App\Http\Controllers\MerchantPortal\ListMerchantRedemptionsController;
use App\Http\Controllers\MerchantPortal\ListMerchantTransactionsController;
use App\Http\Controllers\MerchantPortal\ListMerchantUsersController;
use App\Http\Controllers\MerchantPortal\ListSettlementsController;
use App\Http\Controllers\MerchantPortal\RefundRedemptionController;
use App\Http\Controllers\MerchantPortal\RemoveMerchantStaffController;
use App\Http\Controllers\MerchantPortal\UpdateCreditPackageController;
use App\Http\Controllers\MerchantPortal\UpdateMerchantProfileController;
use App\Http\Controllers\MerchantPortal\UpdateMerchantStaffController;
use App\Http\Controllers\MerchantPortal\UpdateMerchantUserPasswordController;
use App\Http\Controllers\Merchants\GetMerchantController;
use App\Http\Controllers\Merchants\GetMerchantsController;
use App\Http\Controllers\Merchants\ListMerchantCreditPackagesController;
use Illuminate\Support\Facades\Route;

// Customer facing - Browse and discover merchants
Route::prefix('merchants')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', GetMerchantsController::class);
    Route::get('/{id}', GetMerchantController::class);
    Route::get('/{id}/packages', ListMerchantCreditPackagesController::class);
});

// Merchant auth - Public endpoints
Route::prefix('merchant-auth')->group(function () {
    Route::post('/login', LoginMerchantUserController::class)->middleware('throttle:login');
    Route::post('/forgot-password', ForgotMerchantPasswordController::class)->middleware('throttle:forgot-password');

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
        Route::get('/me', GetAuthenticatedMerchantUserController::class);
        Route::patch('/me/password', UpdateMerchantUserPasswordController::class)
            ->middleware('merchant.owner');

        Route::get('/', ListMerchantUsersController::class);
    });

    Route::prefix('staff')->middleware('merchant.owner')->group(function () {
        Route::post('/', CreateMerchantStaffController::class);
        Route::patch('/{id}', UpdateMerchantStaffController::class);
        Route::delete('/{id}', RemoveMerchantStaffController::class);
    });

    Route::get('/stats', GetMerchantStatsController::class);
    Route::get('/transactions', ListMerchantTransactionsController::class);

    Route::prefix('redemptions')->group(function () {
        Route::post('/qr', GenerateRedemptionQrController::class)->middleware('throttle:redemption-qr');
        Route::post('/{id}/refund', RefundRedemptionController::class)->middleware('merchant.owner');
        Route::get('/', ListMerchantRedemptionsController::class);
        Route::get('/today', GetTodayRedemptionSummaryController::class);
    });

    Route::prefix('settlements')->middleware('merchant.owner')->group(function () {
        Route::get('/', ListSettlementsController::class);
        Route::get('/{id}', GetSettlementController::class);
        Route::get('/{id}/invoice', GetSettlementInvoiceController::class);
    });

    Route::get('/packages', ListCreditPackagesController::class);

    Route::prefix('packages')->middleware('merchant.owner')->group(function () {
        Route::post('/', CreateCreditPackageController::class);
        Route::patch('/{id}', UpdateCreditPackageController::class);
        Route::delete('/{id}', DeleteCreditPackageController::class);
    });
});
