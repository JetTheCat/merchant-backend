<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantArea;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MerchantLocation>
 */
class MerchantLocationFactory extends Factory
{
    protected $model = MerchantLocation::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->randomElement(['Main Branch', 'Branch 2', 'Outlet', 'Kiosk']),
            'redemption_code' => Str::uuid()->toString(),
            'area' => fake()->randomElement(MerchantArea::cases()),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
