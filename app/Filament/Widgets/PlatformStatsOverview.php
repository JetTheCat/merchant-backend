<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('GMV (All Time)', 'RM ' . number_format($this->getGmv(), 2)),
            Stat::make('GMV (This Month)', 'RM ' . number_format($this->getGmvThisMonth(), 2)),
            Stat::make('Platform Fees (All Time)', 'RM ' . number_format($this->getPlatformFees(), 2)),
            Stat::make('Active Merchants', Merchant::active()->count()),
            Stat::make('Total Users', User::count()),
        ];
    }

    private function getGmv(): float
    {
        return (float) CreditTransaction::purchases()
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('paid_amount');
    }

    private function getGmvThisMonth(): float
    {
        return (float) CreditTransaction::purchases()
            ->where('status', TransactionStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('paid_amount');
    }

    private function getPlatformFees(): float
    {
        return (float) CreditTransaction::purchases()
            ->where('status', TransactionStatus::COMPLETED)
            ->sum('platform_fee');
    }
}
