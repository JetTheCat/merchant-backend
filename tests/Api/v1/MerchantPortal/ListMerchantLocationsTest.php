<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MalaysianState;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantLocationsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private const array VALID_LOCATION_RESPONSE_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'merchantId',
                'name',
                'redemptionCode',
                'street',
                'city',
                'postcode',
                'state',
                'isActive',
                'createdAt',
                'updatedAt',
            ],
        ],
        'meta' => [
            'perPage',
            'nextCursor',
            'prevCursor',
            'hasMore',
        ],
    ];

    private Merchant $merchant;

    private MerchantUser $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanListMerchantLocations(): void
    {
        MerchantLocation::factory()->count(3)->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(self::VALID_LOCATION_RESPONSE_STRUCTURE);
    }

    public function testStaffCanListMerchantLocations(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();
        MerchantLocation::factory()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testOnlyReturnsLocationsFromOwnMerchant(): void
    {
        MerchantLocation::factory()->for($this->merchant)->create();

        $otherMerchant = Merchant::factory()->create();
        MerchantLocation::factory()->count(2)->for($otherMerchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.merchantId', $this->merchant->id);
    }

    public function testInactiveLocationsAreIncluded(): void
    {
        MerchantLocation::factory()->active()->for($this->merchant)->create();
        MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testResponseContainsAddressFields(): void
    {
        MerchantLocation::factory()->for($this->merchant)->create([
            'street' => 'No. 12, Jalan SS15/4a',
            'city' => 'Subang Jaya',
            'postcode' => '47500',
            'state' => MalaysianState::SELANGOR,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'street' => 'No. 12, Jalan SS15/4a',
                'city' => 'Subang Jaya',
                'postcode' => '47500',
                'state' => 'Selangor',
            ]);
    }

    public function testEmptyListWhenNoLocations(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
    }

    public function testCanFilterByActiveStatus(): void
    {
        MerchantLocation::factory()->active()->for($this->merchant)->create();
        MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?is_active=true');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isActive', true);
    }

    public function testCanFilterByInactiveStatus(): void
    {
        MerchantLocation::factory()->active()->for($this->merchant)->create();
        MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?is_active=false');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.isActive', false);
    }

    public function testCanFilterByState(): void
    {
        MerchantLocation::factory()->for($this->merchant)->create(['state' => MalaysianState::SELANGOR]);
        MerchantLocation::factory()->for($this->merchant)->create(['state' => MalaysianState::PENANG]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?state=Selangor');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.state', 'Selangor');
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        MerchantLocation::factory()->count(10)->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?limit=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJson([
                'meta' => [
                    'perPage' => 5,
                    'hasMore' => true
                ]
            ]);
    }

    public function testPaginationCursorNavigatesToNextPage(): void
    {
        MerchantLocation::factory()->count(5)->for($this->merchant)->create();

        $firstPage = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?limit=3');

        $nextCursor = $firstPage->json('meta.nextCursor');

        $secondPage = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?'.http_build_query(['limit' => 3, 'cursor' => $nextCursor]));

        $secondPage->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'meta' => [
                    'hasMore' => false,
                    'nextCursor' => null
                ]
            ]);
    }

    public function testLimitExceedingMaxReturnsValidationError(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?limit=101');

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['limit']);
    }

    public function testCanFilterByName(): void
    {
        MerchantLocation::factory()->for($this->merchant)->create(['name' => 'Main Branch']);
        MerchantLocation::factory()->for($this->merchant)->create(['name' => 'Petaling Store']);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?name=main+br');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Main Branch');
    }

    public function testInvalidFilterValuesReturnValidationError(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint().'?state=Atlantis');

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['state']);
    }

    public function testListMerchantLocationsRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotListMerchantLocations(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
