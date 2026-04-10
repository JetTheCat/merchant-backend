<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

require __DIR__.'/payments.php';

Route::middleware(['snake.request', 'camel.response'])->group(function () {
    require __DIR__.'/auth.php';
    require __DIR__.'/user.php';
    require __DIR__.'/categories.php';
    require __DIR__.'/merchant.php';
});
