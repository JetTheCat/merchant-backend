<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantOwnerTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanAssignTheirOwnLocation(): void
    {
        $location = MerchantLocation::factory()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => $location->id,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Profile updated successfully.'])
            ->assertJsonPath('data.merchantLocationId', $location->id);

        $this->assertDatabaseHas('merchant_users', [
            'id' => $this->owner->id,
            'merchant_location_id' => $location->id,
        ]);
    }

    public function testOwnerCanUnassignTheirOwnLocation(): void
    {
        $location = MerchantLocation::factory()->for($this->merchant)->create();
        $this->owner->update(['merchant_location_id' => $location->id]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => null,
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.merchantLocationId', null);

        $this->assertDatabaseHas('merchant_users', [
            'id' => $this->owner->id,
            'merchant_location_id' => null,
        ]);
    }

    public function testOwnerCannotAssignToAnotherMerchantsLocation(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherLocation = MerchantLocation::factory()->for($otherMerchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => $otherLocation->id,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testOwnerCannotAssignToARemovedLocation(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();
        $location->delete();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => $location->id,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testInvalidLocationIdReturnsValidationError(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => Str::uuid()->toString(),
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testStaffCannotAccessThisEndpoint(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => null,
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $response = $this->patchJson(self::updateMerchantOwnerEndpoint(), [
            'merchantLocationId' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotAccessThisEndpoint(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson(self::updateMerchantOwnerEndpoint(), [
                'merchantLocationId' => null,
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
