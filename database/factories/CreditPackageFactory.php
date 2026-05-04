<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\CreditPackage>
 */
class CreditPackageFactory extends Factory
{
    public function definition(): array
    {
        $creditAmount = $this->faker->randomElement([50, 100, 200, 300, 500]);
        $sellingPrice = round($creditAmount * 0.9, 2);

        return [
            'merchant_id' => Merchant::factory(),
            'name' => 'RM '.$creditAmount.' Credit',
            'description' => $this->faker->optional()->sentence(),
            'credit_amount' => $creditAmount,
            'selling_price' => $sellingPrice,
            'discount_percentage' => 10.00,
            'is_active' => false,
            'purchase_limit_per_user' => null,
            'total_quantity' => null,
            'sold_quantity' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withLimit(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'total_quantity' => $quantity,
        ]);
    }

    public function withUserLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_limit_per_user' => $limit,
        ]);
    }
}
