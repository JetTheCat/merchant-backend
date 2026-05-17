<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantStatus;
use App\Enums\SettlementCycle;
use App\Models\Category;
use App\Models\Merchant;
use Database\Seeders\CategorySeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        $categorySlugs = array_column(CategorySeeder::CATEGORIES_DATA, 'slug');
        $category = Category::where('slug', fake()->randomElement($categorySlugs))->first();

        return [
            'name' => fake()->company(),
            'code' => strtoupper(fake()->unique()->lexify('??????##')),
            'business_name' => fake()->company().' Sdn Bhd',
            'business_registration_number' => fake()->numerify('##########'),
            'category_id' => $category?->id ?? Category::factory(),
            'description' => fake()->paragraph(3),
            'logo_url' => fake()->imageUrl(200, 200, 'business', true),
            'cover_image_url' => fake()->imageUrl(800, 400, 'business', true),
            'phone' => fake()->numerify('+60#########'),
            'website' => fake()->optional(0.7)->url(),
            'address' => fake()->streetAddress(),
            'city' => fake()->randomElement(['Kuala Lumpur', 'Petaling Jaya', 'Shah Alam', 'Subang Jaya', 'Penang', 'Johor Bahru']),
            'state' => fake()->randomElement(['Kuala Lumpur', 'Selangor', 'Penang', 'Johor']),
            'postcode' => fake()->numerify('#####'),
            'latitude' => fake()->latitude(2.5, 6.5),
            'longitude' => fake()->longitude(99.5, 104.5),
            'platform_fee_rate' => fake()->randomFloat(4, 0.03, 0.10),
            'settlement_cycle' => fake()->randomElement(SettlementCycle::cases())->value,
            'status' => MerchantStatus::ACTIVE->value,
            'bank_name' => fake()->randomElement(['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank']),
            'bank_account_number' => fake()->numerify('##############'),
            'bank_account_name' => fake()->company(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantStatus::ACTIVE->value,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantStatus::SUSPENDED->value,
        ]);
    }

    public function forCategory(string $categoryId): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }

    public function withoutBanking(): static
    {
        return $this->state(fn (array $attributes) => [
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);
    }
}
