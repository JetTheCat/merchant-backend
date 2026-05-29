<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantLocationTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $owner;

    private MerchantLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
        $this->location = MerchantLocation::factory()->active()->for($this->merchant)->create([
            'name' => 'Main Branch',
            'street' => 'No. 12, Jalan SS15/4a',
            'city' => 'Subang Jaya',
            'postcode' => '47500',
            'state' => 'Selangor',
        ]);
    }

    public function testOwnerCanUpdateLocationName(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'name' => 'Updated Branch',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Location updated successfully.'])
            ->assertJsonPath('data.name', 'Updated Branch');

        $this->assertDatabaseHas('merchant_locations', [
            'id' => $this->location->id,
            'name' => 'Updated Branch',
        ]);
    }

    public function testOwnerCanUpdateLocationAddress(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'street' => 'No. 5, Jalan PJ/1',
                'city' => 'Petaling Jaya',
                'postcode' => '46050',
                'state' => 'Selangor',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonFragment([
                'street' => 'No. 5, Jalan PJ/1',
                'city' => 'Petaling Jaya',
                'postcode' => '46050',
                'state' => 'Selangor',
            ]);

        $this->assertDatabaseHas('merchant_locations', [
            'id' => $this->location->id,
            'street' => 'No. 5, Jalan PJ/1',
            'city' => 'Petaling Jaya',
            'postcode' => '46050',
            'state' => 'Selangor',
        ]);
    }

    public function testRedemptionCodeIsNotUpdatable(): void
    {
        $originalCode = $this->location->redemption_code;

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'name' => 'Updated Name',
                'redemption_code' => Str::uuid()->toString(),
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.redemptionCode', $originalCode);

        $this->assertDatabaseHas('merchant_locations', [
            'id' => $this->location->id,
            'redemption_code' => $originalCode,
        ]);
    }

    public function testOwnerCannotUpdateLocationFromAnotherMerchant(): void
    {
        $otherLocation = MerchantLocation::factory()
            ->for(Merchant::factory())
            ->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($otherLocation->id), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentLocation(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint(Str::uuid()->toString()), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    #[DataProvider('providesInvalidPayloads')]
    public function testOwnerCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function providesInvalidPayloads(): array
    {
        return [
            'name too long' => [
                'payload' => ['name' => str_repeat('a', 101)],
                'expectedErrors' => ['name'],
            ],
            'street too long' => [
                'payload' => ['street' => str_repeat('a', 256)],
                'expectedErrors' => ['street'],
            ],
            'city too long' => [
                'payload' => ['city' => str_repeat('a', 101)],
                'expectedErrors' => ['city'],
            ],
            'postcode not digits' => [
                'payload' => ['postcode' => 'ABCDE'],
                'expectedErrors' => ['postcode'],
            ],
            'postcode wrong length' => [
                'payload' => ['postcode' => '1234'],
                'expectedErrors' => ['postcode'],
            ],
            'invalid state' => [
                'payload' => ['state' => 'Atlantis'],
                'expectedErrors' => ['state'],
            ],
            'is_active not boolean' => [
                'payload' => ['is_active' => 'yes'],
                'expectedErrors' => ['isActive'],
            ],
        ];
    }

    public function testOwnerCanActivateLocation(): void
    {
        $this->location->update(['is_active' => false]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'is_active' => true,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('merchant_locations', [
            'id' => $this->location->id,
            'is_active' => true,
        ]);
    }

    public function testOwnerCanDeactivateLocation(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'is_active' => false,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('merchant_locations', [
            'id' => $this->location->id,
            'is_active' => false,
        ]);
    }

    public function testStaffCannotUpdateLocation(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testUpdateLocationRequiresAuthentication(): void
    {
        $response = $this->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotUpdateLocation(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson(self::updateMerchantLocationEndpoint($this->location->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
