<?php

use App\Http\Controllers\Users\GetUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', GetUserController::class);
});
