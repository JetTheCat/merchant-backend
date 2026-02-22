<?php

declare(strict_types=1);

use App\Http\Controllers\Users\GetUserController;
use App\Http\Controllers\Users\UpdateUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', GetUserController::class);
    Route::patch('/', UpdateUserController::class);
});
