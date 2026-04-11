<?php

declare(strict_types=1);

use App\Http\Controllers\Payments\GetPaymentController;
use App\Http\Controllers\Payments\PurchaseCreditPackageController;
use App\Http\Controllers\Payments\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/stripe/webhook', StripeWebhookController::class);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/packages/{package}/purchase', PurchaseCreditPackageController::class);
    Route::get('/payments/{payment}', GetPaymentController::class);
});
