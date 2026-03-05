<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('merchant')->middleware(['auth:sanctum'])->group(function () {
});
