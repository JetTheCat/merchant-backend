<?php

declare(strict_types=1);

use App\Http\Controllers\Users\ConfirmRedemptionController;
use App\Http\Controllers\Users\GetCreditTransactionController;
use App\Http\Controllers\Users\GetUserController;
use App\Http\Controllers\Users\GetUserCreditController;
use App\Http\Controllers\Users\ListCreditTransactionsController;
use App\Http\Controllers\Users\ListUserCreditsController;
use App\Http\Controllers\Users\LookupRedemptionQrController;
use App\Http\Controllers\Users\UpdatePasswordController;
use App\Http\Controllers\Users\UpdateUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware(['auth:sanctum', 'verified', 'user.active'])->group(function () {
    Route::get('/', GetUserController::class);
    Route::patch('/', UpdateUserController::class);
    Route::patch('/password', UpdatePasswordController::class);

    Route::prefix('credits')->group(function () {
        Route::get('/', ListUserCreditsController::class);
        Route::get('/redeem/{code}', LookupRedemptionQrController::class);
        Route::post('/redeem', ConfirmRedemptionController::class);
        Route::get('/transactions', ListCreditTransactionsController::class);
        Route::get('/transactions/{transaction}', GetCreditTransactionController::class);
        Route::get('/{merchantId}', GetUserCreditController::class);
    });
});
