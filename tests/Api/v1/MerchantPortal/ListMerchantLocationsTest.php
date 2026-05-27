<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantArea;
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

    private const VALID_LOCATION_RESPONSE_FIELDS = [
        'id',
        'merchantId',
        'name',
        'redemptionCode',
        'area',
        'state',
        'isActive',
        'createdAt',
        'updatedAt',
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
            ->assertJsonStructure([
                'data' => [
                    '*' => self::VALID_LOCATION_RESPONSE_FIELDS,
                ],
            ]);
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
        MerchantLocation::factory()->for($this->merchant)->create();
        MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testResponseContainsAreaAndState(): void
    {
        MerchantLocation::factory()->for($this->merchant)->create([
            'area' => MerchantArea::PETALING_JAYA,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.area', 'Petaling Jaya')
            ->assertJsonPath('data.0.state', 'Selangor');
    }

    public function testEmptyListWhenNoLocations(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantLocationsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
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
