<?php

declare(strict_types=1);

use App\Http\Controllers\Categories\GetCategoryController;
use App\Http\Controllers\Categories\ListCategoriesController;
use Illuminate\Support\Facades\Route;

Route::prefix('categories')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', ListCategoriesController::class);
    Route::get('/{id}', GetCategoryController::class);
});
