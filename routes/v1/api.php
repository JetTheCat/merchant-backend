<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['snake.request', 'camel.response'])->group(function () {
    require __DIR__.'/auth.php';
    require __DIR__.'/user.php';
});
