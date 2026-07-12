<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DownloadPlatformFeeReportController;
use App\Http\Controllers\Admin\DownloadSettlementInvoiceController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/settlements/{settlement}/invoice', DownloadSettlementInvoiceController::class)
        ->name('admin.settlements.invoice');

    Route::get('/admin/platform-fee-reports/{platformFeeReport}/download', DownloadPlatformFeeReportController::class)
        ->name('admin.platform-fee-reports.download');
});
