<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantEndpoints;
use Tests\TestCase;

class GetMerchantsTest extends TestCase
{
    use RefreshDatabase, MerchantEndpoints;

    private User $user;

    private const VALID_MERCHANT_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'name',
                'businessName',
                'description',
                'logoUrl',
                'coverImageUrl',
                'phone',
                'website',
                'address',
                'city',
                'state',
                'postcode',
                'latitude',
                'longitude',
                'category' => [
                    'id',
                    'name',
                    'slug',
                    'description',
                    'icon',
                    'isActive',
                    'type',
                ],
            ],
        ],
        'meta' => [
            'perPage',
            'nextCursor',
            'prevCursor',
            'hasMore',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('merchants')->delete();
        DB::table('categories')->delete();
        
        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->create();
    }

    public function testUserCanFetchListOfMerchants(): void
    {
        Merchant::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_MERCHANT_STRUCTURE)
            ->assertJsonCount(5, 'data');
    }

    public function testItOnlyReturnsActiveMerchants(): void
    {
        Merchant::factory()->count(3)->create(['status' => 'active']);
        Merchant::factory()->count(2)->create(['status' => 'suspended']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');
    }

    public function testItCanFilterByCategory(): void
    {
        $restaurantCategory = Category::where('slug', 'restaurants')->first();
        $shoppingCategory = Category::where('slug', 'shopping')->first();

        Merchant::factory()->count(3)->create(['category_id' => $restaurantCategory->id]);
        Merchant::factory()->count(2)->create(['category_id' => $shoppingCategory->id]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['category' => 'restaurants']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');

        $data = $response->json('data');
        foreach ($data as $merchant) {
            $this->assertEquals('restaurants', $merchant['category']['slug']);
        }
    }

    public function testItCanFilterByCity(): void
    {
        Merchant::factory()->count(2)->create(['city' => 'Kuala Lumpur']);
        Merchant::factory()->count(3)->create(['city' => 'Penang']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['city' => 'Kuala Lumpur']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');

        $data = $response->json('data');
        foreach ($data as $merchant) {
            $this->assertEquals('Kuala Lumpur', $merchant['city']);
        }
    }

    public function testItCanFilterByState(): void
    {
        Merchant::factory()->count(4)->create(['state' => 'Selangor']);
        Merchant::factory()->count(2)->create(['state' => 'Johor']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['state' => 'Selangor']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(4, 'data');

        $data = $response->json('data');
        foreach ($data as $merchant) {
            $this->assertEquals('Selangor', $merchant['state']);
        }
    }

    public function testItCanSearchByName(): void
    {
        Merchant::factory()->create(['name' => 'Amazing Coffee Shop']);
        Merchant::factory()->create(['name' => 'Best Pizza Place']);
        Merchant::factory()->create(['business_name' => 'Coffee Beans Sdn Bhd']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['search' => 'coffee']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanSearchByBusinessName(): void
    {
        Merchant::factory()->create(['business_name' => 'Tech Solutions Sdn Bhd']);
        Merchant::factory()->create(['business_name' => 'Food Services Sdn Bhd']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['search' => 'tech']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testItCanSearchByDescription(): void
    {
        Merchant::factory()->create(['description' => 'We serve authentic Italian cuisine']);
        Merchant::factory()->create(['description' => 'Modern Japanese restaurant']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['search' => 'italian']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testSearchIsCaseInsensitive(): void
    {
        Merchant::factory()->create(['name' => 'Amazing Coffee Shop']);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['search' => 'COFFEE']));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testItCanCombineMultipleFilters(): void
    {
        $restaurantCategory = Category::where('slug', 'restaurants')->first();

        Merchant::factory()->create([
            'category_id' => $restaurantCategory->id,
            'city' => 'Kuala Lumpur',
            'state' => 'Kuala Lumpur',
            'name' => 'Italian Restaurant',
        ]);

        Merchant::factory()->create([
            'category_id' => $restaurantCategory->id,
            'city' => 'Penang',
            'state' => 'Penang',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'category' => 'restaurants',
                'city' => 'Kuala Lumpur',
                'state' => 'Kuala Lumpur',
            ]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testItSupportsCursorPagination(): void
    {
        Merchant::factory()->count(30)->create();

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['limit' => 10]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'meta' => [
                    'perPage',
                    'nextCursor',
                    'prevCursor',
                    'hasMore',
                ],
            ])
            ->assertJsonPath('meta.perPage', 10)
            ->assertJsonPath('meta.hasMore', true);

        $nextCursor = $response->json('meta.nextCursor');
        $this->assertNotNull($nextCursor);
    }

    public function testItCanNavigateToNextPage(): void
    {
        Merchant::factory()->count(25)->create();

        $firstResponse = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['limit' => 10]));

        $nextCursor = $firstResponse->json('meta.nextCursor');

        $secondResponse = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'limit' => 10,
                'cursor' => $nextCursor,
            ]));

        $secondResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(10, 'data');

        $firstPageIds = collect($firstResponse->json('data'))->pluck('id')->toArray();
        $secondPageIds = collect($secondResponse->json('data'))->pluck('id')->toArray();

        $this->assertEmpty(array_intersect($firstPageIds, $secondPageIds));
    }

    public function testItRespectsCustomLimit(): void
    {
        Merchant::factory()->count(50)->create();

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['limit' => 20]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.perPage', 20);
    }

    public function testItUsesDefaultLimitWhenNotProvided(): void
    {
        Merchant::factory()->count(50)->create();

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('meta.perPage', 25);
    }

    public function testItReturnsEmptyArrayWhenNoMerchantsExist(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['data' => []])
            ->assertJsonCount(0, 'data');
    }

    public function testItReturnsValidationErrorForInvalidCategory(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['category' => 'invalid-category']));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['category']);
    }

    public function testItReturnsValidationErrorForInvalidLimit(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['limit' => 0]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['limit']);
    }

    public function testItReturnsValidationErrorForLimitAboveMax(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint(['limit' => 101]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['limit']);
    }

    public function testUnauthenticatedUserCannotFetchMerchants(): void
    {
        $response = $this->getJson($this->listMerchantsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
