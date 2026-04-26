<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $balanceBefore = $this->faker->randomFloat(2, 0, 500);
        $amount = $this->faker->randomFloat(2, 10, 200);

        return [
            'user_id' => User::factory(),
            'merchant_id' => Merchant::factory(),
            'user_credit_id' => null,
            'credit_package_id' => null,
            'merchant_user_id' => null,
            'category' => TransactionCategory::CREDIT,
            'type' => TransactionType::CREDIT_PURCHASE,
            'reference_code' => Transaction::generateReferenceCode(),
            'status' => TransactionStatus::COMPLETED,
            'amount' => $amount,
            'paid_amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => round($balanceBefore + $amount, 2),
            'qr_code' => null,
            'qr_expires_at' => null,
            'redemption_code' => null,
            'redeemed_at' => null,
            'expires_at' => null,
            'expired_at' => null,
            'metadata' => null,
            'notes' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => TransactionStatus::COMPLETED]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TransactionStatus::PENDING]);
    }

    public function failed(): static
    {
        return $this->state(['status' => TransactionStatus::FAILED]);
    }
}
