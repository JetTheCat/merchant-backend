<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RevenueChartWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            PlatformStatsOverview::class,
            RevenueChartWidget::class,
        ];
    }
}
