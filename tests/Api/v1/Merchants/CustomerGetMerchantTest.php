<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Models\Merchant;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantEndpoints;
use Tests\TestCase;

class CustomerGetMerchantTest extends TestCase
{
    use MerchantEndpoints, RefreshDatabase;

    private User $user;

    private Merchant $merchant;

    private const VALID_MERCHANT_STRUCTURE = [
        'data' => [
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
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('merchants')->delete();
        DB::table('categories')->delete();

        $this->seed(CategorySeeder::class);
        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->create(['status' => 'active']);
    }

    public function testUserCanFetchMerchantById(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->getMerchantEndpoint($this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_MERCHANT_STRUCTURE)
            ->assertJson([
                'data' => [
                    'id' => $this->merchant->id,
                    'name' => $this->merchant->name,
                    'businessName' => $this->merchant->business_name,
                ],
            ]);
    }

    public function testItReturnsNotFoundForInvalidMerchantId(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson($this->getMerchantEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Merchant not found.',
            ]);
    }

    public function testItReturnsNotFoundForSuspendedMerchant(): void
    {
        $suspendedMerchant = Merchant::factory()->suspended()->create();

        $response = $this->actingAs($this->user)
            ->getJson($this->getMerchantEndpoint($suspendedMerchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND)
            ->assertJson([
                'message' => 'Merchant not found.',
            ]);
    }

    public function testItHandlesNullableFields(): void
    {
        $merchant = Merchant::factory()->create([
            'website' => null,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson($this->getMerchantEndpoint($merchant->id));

        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertNull($data['website']);
    }

    public function testUnauthenticatedUserCannotFetchMerchant(): void
    {
        $response = $this->getJson($this->getMerchantEndpoint($this->merchant->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
