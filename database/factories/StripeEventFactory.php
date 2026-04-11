<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\StripeEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StripeEvent>
 */
class StripeEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'type' => 'payment_intent.succeeded',
            'payload' => [],
            'processed_at' => null,
        ];
    }

    public function processed(): static
    {
        return $this->state(['processed_at' => now()]);
    }
}
