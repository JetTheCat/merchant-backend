<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Storage\PlatformFeeReportStorageInterface;
use App\Services\Storage\PlatformFeeReportStorageService;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PlatformFeeReportStorageInterface::class, PlatformFeeReportStorageService::class);
    }
}
