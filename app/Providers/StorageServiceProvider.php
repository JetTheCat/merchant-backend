<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Storage\FeeReportStorageInterface;
use App\Services\Storage\FeeReportStorageService;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeeReportStorageInterface::class, FeeReportStorageService::class);
    }
}
