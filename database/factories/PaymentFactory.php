<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'transaction_id' => null,
            'payment_method' => $this->faker->randomElement(PaymentMethod::cases()),
            'stripe_payment_intent_id' => 'pi_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'stripe_charge_id' => null,
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'MYR',
            'status' => PaymentStatus::REQUIRES_PAYMENT_METHOD,
            'stripe_payload' => null,
            'failure_reason' => null,
            'paid_at' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::SUCCEEDED,
            'stripe_charge_id' => 'ch_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'paid_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(['status' => PaymentStatus::PROCESSING]);
    }

    public function canceled(): static
    {
        return $this->state([
            'status' => PaymentStatus::CANCELED,
            'failure_reason' => 'Your card was declined.',
        ]);
    }
}
