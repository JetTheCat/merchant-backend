<?php

declare(strict_types=1);

use App\Http\Controllers\Payments\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/payments/stripe/webhook', StripeWebhookController::class);
