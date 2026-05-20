<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class RemoveMerchantStaffTest extends TestCase
{
    use MerchantPortalEndpoints;

    private MerchantUser $owner;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanRemoveStaffUser(): void
    {
        $staff = MerchantUser::factory()
            ->staff()
            ->active()
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Merchant user removed successfully.']);

        $this->assertSoftDeleted('merchant_users', ['id' => $staff->id]);
    }

    public function testOwnerCannotRemoveOwnAccount(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint($this->owner->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotRemoveAnotherOwner(): void
    {
        $anotherOwner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint($anotherOwner->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotRemoveStaffUserFromAnotherMerchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherStaff = MerchantUser::factory()->staff()->active()->for($otherMerchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint($otherStaff->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $this->assertNotSoftDeleted('merchant_users', ['id' => $otherStaff->id]);
    }

    public function testStaffCannotRemoveStaffUser(): void
    {
        [$staff, $anotherStaff] = MerchantUser::factory()
            ->staff()
            ->active()
            ->for($this->merchant)
            ->count(2)
            ->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint($anotherStaff->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testReturnsNotFoundForNonExistentStaffUser(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantStaffEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRemoveStaffUserRequiresAuthentication(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->deleteJson(self::deleteMerchantStaffEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotRemoveStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->deleteJson(self::deleteMerchantStaffEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
