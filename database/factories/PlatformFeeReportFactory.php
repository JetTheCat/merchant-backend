<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FeeReportStatus;
use App\Models\PlatformFeeReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformFeeReport>
 */
class PlatformFeeReportFactory extends Factory
{
    protected $model = PlatformFeeReport::class;

    public function definition(): array
    {
        $periodStart = now()->subDays(30);
        $periodEnd = $periodStart->clone()->addDays(13);

        $totalGmv = fake()->randomFloat(2, 1000, 50000);
        $totalPlatformFees = round($totalGmv * 0.05, 2);
        $totalProcessingCosts = round($totalGmv * 0.03, 2);

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'file_path' => sprintf('fee-reports/%s/%s.csv', $periodStart->format('Y'), fake()->uuid()),
            'total_gmv' => $totalGmv,
            'total_platform_fees' => $totalPlatformFees,
            'total_processing_costs' => $totalProcessingCosts,
            'status' => FeeReportStatus::PENDING,
            'payment_reference' => null,
            'paid_at' => null,
            'generated_by' => User::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => FeeReportStatus::PAID,
            'payment_reference' => fake()->bothify('PAY-########'),
            'paid_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}
