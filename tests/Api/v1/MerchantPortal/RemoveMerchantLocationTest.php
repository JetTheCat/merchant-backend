<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class RemoveMerchantLocationTest extends TestCase
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

    public function testOwnerCanRemoveInactiveLocation(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_NO_CONTENT);
    }

    public function testLocationIsHardDeletedWhenNoTransactionsExist(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('merchant_locations', ['id' => $location->id]);
    }

    public function testLocationIsSoftDeletedWhenTransactionsExist(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'merchant_location_id' => $location->id,
        ]);

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertSoftDeleted('merchant_locations', ['id' => $location->id]);
    }

    public function testCannotRemoveActiveLocation(): void
    {
        $location = MerchantLocation::factory()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['message' => 'Location must be deactivated before it can be removed.']);

        $this->assertNotSoftDeleted('merchant_locations', ['id' => $location->id]);
    }

    public function testStaffAssignmentsAreNullifiedOnRemoval(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'merchant_location_id' => $location->id,
        ]);

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertNull($staff->fresh()->merchant_location_id);
    }

    public function testRemovingLocationOnlyUnlinksStaffAssignedToThatLocation(): void
    {
        [$locationA, $locationB] = MerchantLocation::factory()
            ->inactive()
            ->for($this->merchant)
            ->count(2)
            ->create();

        [$staffA, $staffB] = MerchantUser::factory()
            ->staff()
            ->active()
            ->for($this->merchant)
            ->count(2)
            ->sequence(
                ['merchant_location_id' => $locationA->id],
                ['merchant_location_id' => $locationB->id],
            )
            ->create();

        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($locationA->id))
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertNull($staffA->fresh()->merchant_location_id);
        $this->assertEquals($locationB->id, $staffB->fresh()->merchant_location_id);
    }

    public function testOwnerCannotRemoveAnotherMerchantsLocation(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $location = MerchantLocation::factory()->inactive()->for($otherMerchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);

        $this->assertNotSoftDeleted('merchant_locations', ['id' => $location->id]);
    }

    public function testReturnsNotFoundForNonExistentLocation(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testStaffCannotRemoveLocation(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testRemoveLocationRequiresAuthentication(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        $response = $this->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotRemoveLocation(): void
    {
        $location = MerchantLocation::factory()->inactive()->for($this->merchant)->create();
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->deleteJson(self::removeMerchantLocationEndpoint($location->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
