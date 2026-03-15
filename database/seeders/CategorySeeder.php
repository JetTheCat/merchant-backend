<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const CATEGORIES_DATA = [
        [
            'name' => 'Restaurants',
            'slug' => 'restaurants',
            'description' => 'Dining establishments, cafes, and food services',
            'is_active' => true,
            'type' => CategoryType::MERCHANT,
        ],
        [
            'name' => 'Shopping',
            'slug' => 'shopping',
            'description' => 'Retail stores, boutiques, and shopping centers',
            'is_active' => true,
            'type' => CategoryType::MERCHANT,
        ],
        [
            'name' => 'Hair Salon',
            'slug' => 'hair-salon',
            'description' => 'Hair salons, barbershops, and styling services',
            'is_active' => true,
            'type' => CategoryType::MERCHANT,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = self::CATEGORIES_DATA;

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
