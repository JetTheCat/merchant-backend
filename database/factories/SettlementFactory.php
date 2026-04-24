<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SettlementStatus;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Settlement>
 */
class SettlementFactory extends Factory
{
    protected $model = Settlement::class;

    public function definition(): array
    {
        $totalSales = fake()->randomFloat(2, 100, 5000);
        $feeRate = fake()->randomFloat(4, 0.03, 0.10);
        $totalFees = round($totalSales * $feeRate, 2);
        $netAmount = round($totalSales - $totalFees, 2);
        $periodStart = fake()->dateTimeBetween('-3 months', '-1 month');
        $periodEnd = fake()->dateTimeBetween($periodStart, 'now');

        return [
            'merchant_id' => Merchant::factory(),
            'reference_code' => Transaction::generateReferenceCode(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_sales' => $totalSales,
            'total_fees' => $totalFees,
            'net_amount' => $netAmount,
            'transaction_count' => fake()->numberBetween(1, 50),
            'status' => SettlementStatus::PENDING,
            'bank_name' => fake()->randomElement(['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank']),
            'bank_account_number' => fake()->numerify('##############'),
            'bank_account_name' => fake()->company(),
            'payment_reference' => null,
            'paid_at' => null,
            'payment_proof_url' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SettlementStatus::COMPLETED,
            'payment_reference' => strtoupper(fake()->bothify('TT##########')),
            'paid_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SettlementStatus::PROCESSING,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SettlementStatus::FAILED,
        ]);
    }
}
