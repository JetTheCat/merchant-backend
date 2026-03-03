<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        $icons = [
            'utensils',
            'shopping-bag',
            'briefcase',
            'film',
            'heart',
            'car',
            'scissors',
            'dumbbell',
            'book',
            'plane',
        ];

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(10),
            'icon' => fake()->randomElement($icons),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90),
            'type' => fake()->randomElement([
                CategoryType::MERCHANT->value,
                CategoryType::GENERAL->value,
            ]),
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

    public function merchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'merchant',
        ]);
    }

    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'general',
        ]);
    }
}
