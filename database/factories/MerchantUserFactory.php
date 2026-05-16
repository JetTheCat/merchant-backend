<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MerchantUserRole;
use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MerchantUserFactory extends Factory
{
    protected $model = MerchantUser::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+60#########'),
            'password' => Hash::make('password'),
            'role' => fake()->randomElement(MerchantUserRole::cases())->value,
            'status' => MerchantUserStatus::ACTIVE->value,
            'email_verified_at' => now(),
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days', 'now'),
            'remember_token' => Str::random(10),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MerchantUserRole::OWNER->value,
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MerchantUserRole::STAFF->value,
            'username' => fake()->unique()->userName(),
            'email' => null,
            'email_verified_at' => null,
        ]);
    }

    public function pendingVerification(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantUserStatus::PENDING_VERIFICATION->value,
            'password' => Hash::make('TempPass123!'),
            'email_verified_at' => null,
            'last_login_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantUserStatus::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MerchantUserStatus::SUSPENDED->value,
        ]);
    }

    public function forMerchant(string $merchantId): static
    {
        return $this->state(fn (array $attributes) => [
            'merchant_id' => $merchantId,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
