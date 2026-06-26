<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\UserCredit>
 */
class UserCreditFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'merchant_id' => Merchant::factory(),
            'balance' => 0,
            'cash_balance' => 0,
            'gross_paid_balance' => 0,
            'total_purchased' => 0,
            'total_redeemed' => 0,
            'last_purchase_at' => null,
            'last_redemption_at' => null,
        ];
    }

    public function withBalance(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => $amount,
            'cash_balance' => $amount,
            'gross_paid_balance' => $amount,
            'total_purchased' => $amount,
            'last_purchase_at' => now(),
        ]);
    }
}
