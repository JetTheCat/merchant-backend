<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class UpdateMerchantUserPasswordTest extends TestCase
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
            'password' => Hash::make('OldSecurePass123!'),
        ]);
    }

    public function testOwnerCanUpdateOwnPassword(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'NewSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password updated successfully.',
            ]);

        $this->assertTrue(Hash::check('NewSecurePass123!', $this->owner->fresh()->password));
        $this->assertFalse(Hash::check('OldSecurePass123!', $this->owner->fresh()->password));
    }

    public function testOldPasswordCannotBeUsedAfterUpdate(): void
    {
        $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'NewSecurePass123!',
            ])
            ->assertStatus(Response::HTTP_OK);

        $this->owner->refresh();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'AnotherSecurePass123!',
                'passwordConfirmation' => 'AnotherSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function testUpdateFailsWithIncorrectCurrentPassword(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'WrongPassword123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'NewSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currentPassword']);

        $this->assertTrue(Hash::check('OldSecurePass123!', $this->owner->fresh()->password));
    }

    public function testUpdateFailsWithMismatchedConfirmation(): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'DifferentPass123!',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);

        $this->assertTrue(Hash::check('OldSecurePass123!', $this->owner->fresh()->password));
    }

    #[DataProvider('providesInvalidPasswordData')]
    public function testUpdateFailsWithInvalidPassword(array $payload, array $expectedErrors): void
    {
        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);

        $this->assertTrue(Hash::check('OldSecurePass123!', $this->owner->fresh()->password));
    }

    public static function providesInvalidPasswordData(): array
    {
        return [
            'missing current password' => [
                'payload' => [
                    'password' => 'NewSecurePass123!',
                    'passwordConfirmation' => 'NewSecurePass123!',
                ],
                'expectedErrors' => ['currentPassword'],
            ],
            'missing new password' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'passwordConfirmation' => 'NewSecurePass123!',
                ],
                'expectedErrors' => ['password'],
            ],
            'missing password confirmation' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'password' => 'NewSecurePass123!',
                ],
                'expectedErrors' => ['password'],
            ],
            'weak password' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'password' => 'weak',
                    'passwordConfirmation' => 'weak',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without uppercase' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'password' => 'newsecurepass123!',
                    'passwordConfirmation' => 'newsecurepass123!',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without numbers' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'password' => 'NewSecurePass!',
                    'passwordConfirmation' => 'NewSecurePass!',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => [
                    'currentPassword' => 'OldSecurePass123!',
                    'password' => 'NewSecurePass123',
                    'passwordConfirmation' => 'NewSecurePass123',
                ],
                'expectedErrors' => ['password'],
            ],
        ];
    }

    public function testUpdateRequiresAuthentication(): void
    {
        $response = $this->patchJson(self::patchMerchantUserPasswordEndpoint(), [
            'currentPassword' => 'OldSecurePass123!',
            'password' => 'NewSecurePass123!',
            'passwordConfirmation' => 'NewSecurePass123!',
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testStaffCannotUpdatePassword(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'NewSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson(['message' => 'Only the merchant owner can perform this action.']);
    }

    public function testCustomerCannotUpdatePassword(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson(self::patchMerchantUserPasswordEndpoint(), [
                'currentPassword' => 'OldSecurePass123!',
                'password' => 'NewSecurePass123!',
                'passwordConfirmation' => 'NewSecurePass123!',
            ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
