<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly GMV (Last 12 Months)';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'));

        $revenue = CreditTransaction::purchases()
            ->where('status', TransactionStatus::COMPLETED)
            ->where('created_at', '>=', Carbon::parse($months->first())->startOfMonth())
            ->selectRaw("DATE_TRUNC('month', created_at) as month, SUM(paid_amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->mapWithKeys(fn (string $total, string $month) => [
                Carbon::parse($month)->format('Y-m') => $total,
            ]);

        $dataPoints = $months->map(fn (string $month) => (float) ($revenue[$month] ?? 0))
            ->values()
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'GMV (RM)',
                    'data' => $dataPoints,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months->map(fn (string $month) => Carbon::parse($month)->format('M Y'))->all(),
        ];
    }
}
