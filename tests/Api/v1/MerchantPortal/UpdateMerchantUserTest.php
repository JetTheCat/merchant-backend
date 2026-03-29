<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantUserTest extends TestCase
{
    use MerchantPortalEndpoints;

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

    public function testOwnerCanUpdateMerchantUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($staff->id), [
                'name' => 'Updated Name',
                'phone' => '0123456789',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'merchantId',
                    'name',
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
                ],
            ])
            ->assertJson([
                'message' => 'Merchant user updated successfully.',
                'data' => [
                    'name' => 'Updated Name',
                    'phone' => '0123456789',
                ],
            ]);
    }

    #[DataProvider('merchantUserStatuses')]
    public function testOwnerCanUpdateStaffStatus(MerchantUserStatus $initialStatus, MerchantUserStatus $newStatus): void
    {
        $staff = MerchantUser::factory()->staff()->create([
            'merchant_id' => $this->merchant->id,
            'status' => $initialStatus,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($staff->id), [
                'status' => $newStatus->value,
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($newStatus, $staff->fresh()->status);
    }

    public static function merchantUserStatuses(): array
    {
        return [
            'activate suspended staff' => [MerchantUserStatus::SUSPENDED, MerchantUserStatus::ACTIVE],
            'suspend active staff' => [MerchantUserStatus::ACTIVE, MerchantUserStatus::SUSPENDED],
        ];
    }

    public function testOwnerCannotUpdateAnotherOwner(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($this->owner->id), [
                'name' => 'Updated Owner Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Cannot modify the owner account.']);
    }

    public function testOwnerCannotUpdateUserFromAnotherMerchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherStaff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $otherMerchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($otherStaff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testStaffCannotUpdateMerchantUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $anotherStaff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($anotherStaff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsNotFoundForNonExistentUser(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint(Str::uuid()->toString()), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotSetStatusToPendingVerification(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($staff->id), [
                'status' => MerchantUserStatus::PENDING_VERIFICATION->value,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['status']);
    }

    #[DataProvider('invalidUpdateData')]
    public function testOwnerCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserEndpoint($staff->id), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidUpdateData(): array
    {
        return [
            'name too long' => [
                'payload' => ['name' => str_repeat('a', 256)],
                'expectedErrors' => ['name'],
            ],
            'phone too long' => [
                'payload' => ['phone' => str_repeat('1', 21)],
                'expectedErrors' => ['phone'],
            ],
            'invalid status' => [
                'payload' => ['status' => 'invalid-status'],
                'expectedErrors' => ['status'],
            ],
        ];
    }

    public function testUpdateMerchantUserRequiresAuthentication(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $response = $this->patchJson(self::patchMerchantUserEndpoint($staff->id), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotUpdateMerchantUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $this->merchant->id,
        ]);

        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson(self::patchMerchantUserEndpoint($staff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
