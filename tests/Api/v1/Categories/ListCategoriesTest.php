<?php

declare(strict_types=1);

namespace Tests\Api\v1\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\CategoryEndpoints;
use Tests\TestCase;

class ListCategoriesTest extends TestCase
{
    use CategoryEndpoints;

    private User $user;

    private const VALID_CATEGORY_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'slug',
                'description',
                'icon',
                'sortOrder',
                'isActive',
                'type',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('categories')->truncate();
        $this->user = User::factory()->create();
    }

    public function testUserCanFetchListOfCategories(): void
    {
        Category::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_CATEGORY_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testItOnlyReturnsActiveCategories(): void
    {
        Category::factory()->count(2)->create(['is_active' => true]);
        Category::factory()->count(3)->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItReturnsCategoriesOrderedBySortOrderAndName(): void
    {
        $category1 = Category::factory()->create([
            'name' => 'Retail',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $category2 = Category::factory()->create([
            'name' => 'Food & Beverage',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $category3 = Category::factory()->create([
            'name' => 'Services',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertEquals($category2->id, $data[0]['id']);
        $this->assertEquals($category3->id, $data[1]['id']);
        $this->assertEquals($category1->id, $data[2]['id']);
    }

    public function testItCachesCategoriesList(): void
    {
        Category::factory()->count(2)->create(['is_active' => true]);

        Cache::shouldReceive('tags')
            ->times(2)
            ->with(['categories'])
            ->andReturnSelf();

        Cache::shouldReceive('get')
            ->once()
            ->with('categories.all')
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testItReturnsEmptyArrayWhenNoCategoriesExist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => []])
            ->assertJsonCount(0, 'data');
    }

    public function testUnauthenticatedUserCannotFetchCategories(): void
    {
        $response = $this->getJson($this->listCategoriesEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
