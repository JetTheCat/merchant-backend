<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantUsersTest extends TestCase
{
    use MerchantPortalEndpoints;

    private const VALID_MERCHANT_USER_RESPONSE_FIELDS = [
        'id',
        'merchantId',
        'merchantLocationId',
        'merchantLocation',
        'name',
        'username',
        'email',
        'phone',
        'role',
        'roleLabel',
        'status',
        'statusLabel',
        'emailVerifiedAt',
        'lastLoginAt',
        'createdAt',
        'updatedAt',
    ];

    private MerchantUser $owner;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);
    }

    public function testOwnerCanListMerchantUsers(): void
    {
        MerchantUser::factory()->staff()->active()->count(2)->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => self::VALID_MERCHANT_USER_RESPONSE_FIELDS,
                ],
            ]);
    }

    public function testStaffCanListMerchantUsers(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testUserWithLocationIncludesMerchantLocationData(): void
    {
        $location = MerchantLocation::factory()->for($this->merchant)->create();
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
            'merchant_location_id' => $location->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $staffData = collect($response->json('data'))->firstWhere('id', $staff->id);

        $this->assertEquals($location->id, $staffData['merchantLocationId']);
        $this->assertEquals($location->id, $staffData['merchantLocation']['id']);
        $this->assertEquals($location->name, $staffData['merchantLocation']['name']);
    }

    public function testUserWithoutLocationHasNullMerchantLocation(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.0.merchantLocationId', null)
            ->assertJsonPath('data.0.merchantLocation', null);
    }

    public function testOnlyReturnsMerchantUsersFromOwnMerchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        MerchantUser::factory()->owner()->active()->create([
            'merchant_id' => $otherMerchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.merchantId', $this->merchant->id);
    }

    public function testSoftDeletedUsersAreNotReturned(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $staff->delete();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testListMerchantUsersRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotListMerchantUsers(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson(self::getMerchantUsersEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
