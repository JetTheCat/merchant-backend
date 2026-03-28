<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantUserRole;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class RemoveMerchantUserTest extends TestCase
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

    public function testOwnerCanRemoveMerchantUser(): void
    {
        $staff = MerchantUser::factory()
            ->staff()
            ->active()
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(['message' => 'Merchant user removed successfully.']);

        $this->assertSoftDeleted('merchant_users', ['id' => $staff->id]);
    }

    public function testOwnerCannotRemoveOwnAccount(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint($this->owner->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Cannot remove the owner account.']);
    }

    public function testOwnerCannotRemoveAnotherOwner(): void
    {
        $anotherOwner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint($anotherOwner->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Cannot remove the owner account.']);
    }

    #[DataProvider('providesOtherMerchantUserRoles')]
    public function testOwnerCannotRemoveUserFromAnotherMerchant(MerchantUserRole $role): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherUser = MerchantUser::factory()->active()->for($otherMerchant)->create([
            'role' => $role,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint($otherUser->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
        $this->assertNotSoftDeleted('merchant_users', ['id' => $otherUser->id]);
    }

    public static function providesOtherMerchantUserRoles(): array
    {
        return [
            'owner from another merchant' => [MerchantUserRole::OWNER],
            'staff from another merchant' => [MerchantUserRole::STAFF],
        ];
    }

    public function testStaffCannotRemoveMerchantUser(): void
    {        
        [$staff, $anotherStaff] = MerchantUser::factory()
            ->staff()
            ->active()
            ->for($this->merchant)
            ->count(2)
            ->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint($anotherStaff->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can remove users.']);
    }

    public function testReturnsNotFoundForNonExistentUser(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->deleteJson(self::deleteMerchantUserEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRemoveMerchantUserRequiresAuthentication(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->deleteJson(self::deleteMerchantUserEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotRemoveMerchantUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->deleteJson(self::deleteMerchantUserEndpoint($staff->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
