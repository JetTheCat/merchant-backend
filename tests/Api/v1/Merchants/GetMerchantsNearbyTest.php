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
use Tests\Fixtures\Coordinates;
use Tests\TestCase;

class GetMerchantsNearbyTest extends TestCase
{
    use RefreshDatabase, MerchantEndpoints;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('merchants')->delete();
        DB::table('categories')->delete();
        
        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->create();
    }

    public function testItCanFilterByNearbyLocation(): void
    {
        $nearMerchant = Merchant::factory()->create([
            'latitude' => Coordinates::KLCC['lat'],
            'longitude' => Coordinates::KLCC['lon'],
            'status' => 'active',
        ]);

        $farMerchant = Merchant::factory()->create([
            'latitude' => Coordinates::PENANG['lat'],
            'longitude' => Coordinates::PENANG['lon'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => Coordinates::KLCC['lon'],
                'radius' => 10,
            ]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nearMerchant->id)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['distance'],
                ],
            ]);
    }

    public function testItReturnsDistanceInKilometers(): void
    {
        Merchant::factory()->create([
            'latitude' => Coordinates::KLCC['lat'],
            'longitude' => Coordinates::KLCC['lon'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => Coordinates::KLCC['lon'],
                'radius' => 10,
            ]));

        $response->assertStatus(Response::HTTP_OK);

        $distance = $response->json('data.0.distance');
        $this->assertIsNumeric($distance);
        $this->assertLessThanOrEqual(10, $distance);
    }

    public function testItSortsMerchantsByDistance(): void
    {
        $merchantA = Merchant::factory()->create([
            'city' => 'Petaling Jaya',
            'latitude' => Coordinates::PETALING_JAYA['lat'],
            'longitude' => Coordinates::PETALING_JAYA['lon'],
            'status' => 'active',
        ]);

        $merchantB = Merchant::factory()->create([
            'city' => 'Shah Alam',
            'latitude' => Coordinates::SHAH_ALAM['lat'],
            'longitude' => Coordinates::SHAH_ALAM['lon'],
            'status' => 'active',
        ]);

        $merchantC = Merchant::factory()->create([
            'city' => 'Cheras',
            'latitude' => Coordinates::CHERAS['lat'],
            'longitude' => Coordinates::CHERAS['lon'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => Coordinates::KLCC['lon'],
                'radius' => 50,
            ]));

        $response->assertStatus(Response::HTTP_OK);

        $merchants = $response->json('data');

        // The closest to KLCC is Cheras, then Petaling Jaya, then Shah Alam (all within 50km)
        $this->assertEquals($merchantC->id, $merchants[0]['id']);
        $this->assertLessThan($merchants[1]['distance'], $merchants[0]['distance']);
        $this->assertLessThan($merchants[2]['distance'], $merchants[1]['distance']);
    }

    public function testItValidatesLatitudeRange(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => 91,
                'longitude' => Coordinates::KLCC['lon'],
            ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['latitude']);
    }

    public function testItValidatesLongitudeRange(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => 181,
            ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['longitude']);
    }

    public function testItRequiresLongitudeWhenLatitudeProvided(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
            ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['longitude']);
    }

    public function testItRequiresLatitudeWhenLongitudeProvided(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'longitude' => Coordinates::KLCC['lon'],
            ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['latitude']);
    }

    public function testItValidatesRadiusRange(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => Coordinates::KLCC['lon'],
                'radius' => 101,
            ]));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['radius']);
    }

    public function testItCanCombineLocationWithOtherFilters(): void
    {
        $restaurantCategory = Category::where('slug', 'restaurants')->first();

        $nearRestaurant = Merchant::factory()->create([
            'category_id' => $restaurantCategory->id,
            'latitude' => Coordinates::KLCC['lat'],
            'longitude' => Coordinates::KLCC['lon'],
            'status' => 'active',
        ]);

        $nearSalon = Merchant::factory()->create([
            'category_id' => Category::where('slug', 'hair-salon')->first()->id,
            'latitude' => Coordinates::PETALING_JAYA['lat'],
            'longitude' => Coordinates::PETALING_JAYA['lon'],
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->listMerchantsEndpoint([
                'latitude' => Coordinates::KLCC['lat'],
                'longitude' => Coordinates::KLCC['lon'],
                'radius' => 10,
                'category' => 'restaurants',
            ]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $nearRestaurant->id);
    }
}
