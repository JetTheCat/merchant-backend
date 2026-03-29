<?php

declare(strict_types=1);

namespace Tests\Api\v1\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\CategoryEndpoints;
use Tests\TestCase;

class GetCategoryTest extends TestCase
{
    use CategoryEndpoints;

    private User $user;

    private Category $category;

    private const VALID_CATEGORY_STRUCTURE = [
        'data' => [
            'id',
            'name',
            'slug',
            'description',
            'icon',
            'isActive',
            'type',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('categories')->truncate();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['is_active' => true]);
    }

    public function testUserCanFetchCategoryById(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->getCategoryEndpoint($this->category->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_CATEGORY_STRUCTURE)
            ->assertJson([
                'data' => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ],
            ]);
    }

    public function testItReturnsNotFoundForInvalidCategoryId(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->getCategoryEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Category does not exist.',
            ]);
    }

    public function testItDoesNotReturnInactiveCategory(): void
    {
        $inactiveCategory = Category::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson($this->getCategoryEndpoint($inactiveCategory->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'id' => $inactiveCategory->id,
                    'isActive' => false,
                ],
            ]);
    }

    public function testItCachesCategory(): void
    {
        Cache::shouldReceive('tags')
            ->twice()
            ->with(['categories'])
            ->andReturnSelf();

        Cache::shouldReceive('get')
            ->once()
            ->with("category.{$this->category->id}")
            ->andReturn(null);

        Cache::shouldReceive('put')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)
            ->getJson($this->getCategoryEndpoint($this->category->id));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testUnauthenticatedUserCannotFetchCategory(): void
    {
        $response = $this->getJson($this->getCategoryEndpoint($this->category->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
