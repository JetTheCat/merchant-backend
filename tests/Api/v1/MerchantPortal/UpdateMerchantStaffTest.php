<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantStaffTest extends TestCase
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

    public function testOwnerCanUpdateStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
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

    #[DataProvider('providesMerchantStaffStatuses')]
    public function testOwnerCanUpdateStaffUserStatus(MerchantUserStatus $initialStatus, MerchantUserStatus $newStatus): void
    {
        $staff = MerchantUser::factory()->staff()->for($this->merchant)->create([
            'status' => $initialStatus,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'status' => $newStatus->value,
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($newStatus, $staff->fresh()->status);
    }

    public static function providesMerchantStaffStatuses(): array
    {
        return [
            'activate suspended staff' => [MerchantUserStatus::SUSPENDED, MerchantUserStatus::ACTIVE],
            'suspend active staff' => [MerchantUserStatus::ACTIVE, MerchantUserStatus::SUSPENDED],
        ];
    }

    public function testOwnerCanUpdateStaffPassword(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'password' => 'OldSecurePass123!',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'password' => 'NewSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertTrue(Hash::check('NewSecurePass123!', $staff->fresh()->password));
        $this->assertFalse(Hash::check('OldSecurePass123!', $staff->fresh()->password));
    }

    public function testOwnerCanUpdateStaffUsername(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'oldusername',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'username' => 'newusername',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.username', 'newusername');

        $this->assertDatabaseHas('merchant_users', [
            'id' => $staff->id,
            'username' => 'newusername',
        ]);
    }

    public function testUsernameIsNormalisedToLowercase(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'username' => 'NewUsername',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.username', 'newusername');

        $this->assertDatabaseHas('merchant_users', [
            'id' => $staff->id,
            'username' => 'newusername',
        ]);
    }

    public function testOwnerCanUpdateStaffToSameUsername(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'username' => 'janestaff',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.username', 'janestaff');
    }

    public function testUsernameMustBeUniqueWithinMerchant(): void
    {
        MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'janestaff',
        ]);

        $anotherStaff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create([
            'username' => 'bobstaff',
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($anotherStaff->id), [
                'username' => 'janestaff',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['username']);
    }

    public function testOwnerCannotUpdateOwnAccount(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($this->owner->id), [
                'name' => 'Updated Owner Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotUpdateAnotherOwner(): void
    {
        $anotherOwner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($anotherOwner->id), [
                'name' => 'Updated Owner Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotUpdateStaffUserFromAnotherMerchant(): void
    {
        $otherMerchant = Merchant::factory()->create();
        $otherStaff = MerchantUser::factory()->staff()->active()->create([
            'merchant_id' => $otherMerchant->id,
        ]);

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($otherStaff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentStaffUser(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint(Str::uuid()->toString()), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testOwnerCannotSetStatusToPendingVerification(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'status' => MerchantUserStatus::PENDING_VERIFICATION->value,
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['status']);
    }

    #[DataProvider('providesInvalidUpdateData')]
    public function testOwnerCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function providesInvalidUpdateData(): array
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
            'username with spaces' => [
                'payload' => ['username' => 'jane staff'],
                'expectedErrors' => ['username'],
            ],
            'username with special characters' => [
                'payload' => ['username' => 'jane@staff'],
                'expectedErrors' => ['username'],
            ],
            'username too long' => [
                'payload' => ['username' => str_repeat('a', 51)],
                'expectedErrors' => ['username'],
            ],
            'weak password' => [
                'payload' => ['password' => 'weak'],
                'expectedErrors' => ['password'],
            ],
            'password too short' => [
                'payload' => ['password' => 'Short1!'],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => ['password' => 'NoSymbols123'],
                'expectedErrors' => ['password'],
            ],
            'password without numbers' => [
                'payload' => ['password' => 'NoNumbers!@#'],
                'expectedErrors' => ['password'],
            ],
            'password without mixed case' => [
                'payload' => ['password' => 'nouppercase123!'],
                'expectedErrors' => ['password'],
            ],
        ];
    }

    public function testUpdateStaffUserRequiresAuthentication(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testStaffCannotUpdateStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $anotherStaff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantStaffEndpoint($anotherStaff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testCustomerCannotUpdateStaffUser(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson(self::patchMerchantStaffEndpoint($staff->id), [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
