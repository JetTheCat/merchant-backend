<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClawbackStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\SettlementClawback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettlementClawback>
 */
class SettlementClawbackFactory extends Factory
{
    protected $model = SettlementClawback::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'original_redemption_id' => CreditTransaction::factory()->redemption(),
            'refund_transaction_id' => CreditTransaction::factory()->refund(),
            'amount' => fake()->randomFloat(2, 10, 200),
            'status' => ClawbackStatus::PENDING,
            'settlement_id' => null,
        ];
    }

    public function recovered(): static
    {
        return $this->state(fn () => [
            'status' => ClawbackStatus::RECOVERED,
            'settlement_id' => Settlement::factory(),
        ]);
    }
}
