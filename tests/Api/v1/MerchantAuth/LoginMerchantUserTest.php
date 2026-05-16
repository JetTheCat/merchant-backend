<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Enums\MerchantUserStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantAuthEndpoints;
use Tests\TestCase;

class LoginMerchantUserTest extends TestCase
{
    use MerchantAuthEndpoints;

    private const TEST_PASSWORD = 'SecurePass123!@#';

    private MerchantUser $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchantUser = MerchantUser::factory()->owner()->active()->create([
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);
    }

    public function testOwnerCanLoginWithEmail(): void
    {
        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'token',
                'expiresAt',
                'user' => [
                    'id',
                    'merchantId',
                    'name',
                    'email',
                    'role',
                    'status',
                ],
            ]);

        $this->assertNotNull($response->json('token'));
    }

    public function testStaffCanLoginWithCompositeUsername(): void
    {
        $merchant = Merchant::factory()->active()->create(['code' => 'KOPI88']);

        $staff = MerchantUser::factory()->staff()->active()->for($merchant)->create([
            'username' => 'cashier1',
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => 'cashier1@KOPI88',
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('user.id', $staff->id);
    }

    public function testLoginGeneratesValidToken(): void
    {
        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->merchantUser->id,
            'tokenable_type' => MerchantUser::class,
            'name' => config('sanctum.token_name'),
        ]);
    }

    #[DataProvider('invalidCredentialsData')]
    public function testLoginFailsWithInvalidCredentials(array $payload, array $expectedError): void
    {
        $response = $this->postJson(self::merchantLoginEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(['errors' => $expectedError]);
    }

    public static function invalidCredentialsData(): array
    {
        return [
            'wrong password' => [
                'payload' => [
                    'credential' => 'owner@example.com',
                    'password' => 'WrongPassword123!@#',
                ],
                'expectedError' => [
                    'credentials' => ['Invalid credentials'],
                ],
            ],
            'non-existent email' => [
                'payload' => [
                    'credential' => 'nobody@example.com',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credentials' => ['Invalid credentials'],
                ],
            ],
            'non-existent composite username' => [
                'payload' => [
                    'credential' => 'ghost@INVALID',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credentials' => ['Invalid credentials'],
                ],
            ],
            'empty username part' => [
                'payload' => [
                    'credential' => '@KOPI88',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credential' => ['The credential must be a valid email or a username in the format username@merchantcode'],
                ],
            ],
            'empty merchant code part' => [
                'payload' => [
                    'credential' => 'cashier1@',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credential' => ['The credential must be a valid email or a username in the format username@merchantcode'],
                ],
            ],
            'missing credential' => [
                'payload' => [
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credential' => ['The credential field is required.'],
                ],
            ],
            'missing password' => [
                'payload' => [
                    'credential' => 'owner@example.com',
                ],
                'expectedError' => [
                    'password' => ['The password field is required.'],
                ],
            ],
            'empty payload' => [
                'payload' => [],
                'expectedError' => [
                    'credential' => ['The credential field is required.'],
                    'password' => ['The password field is required.'],
                ],
            ],
        ];
    }

    #[DataProvider('providesNonActiveMerchantUserStatuses')]
    public function testNonActiveMerchantUserCannotLogin(MerchantUserStatus $status): void
    {
        $this->merchantUser->update(['status' => $status->value]);

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Your account is not active. Please contact your administrator.',
            ]);
    }

    public static function providesNonActiveMerchantUserStatuses(): array
    {
        return [
            'pending verification' => [MerchantUserStatus::PENDING_VERIFICATION],
            'suspended' => [MerchantUserStatus::SUSPENDED],
        ];
    }

    public function testNonActiveStaffCannotLogin(): void
    {
        $merchant = Merchant::factory()->active()->create(['code' => 'SHOP01']);

        MerchantUser::factory()->staff()->for($merchant)->create([
            'username' => 'cashier1',
            'status' => MerchantUserStatus::SUSPENDED,
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => 'cashier1@SHOP01',
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testSoftDeletedMerchantUserCannotLogin(): void
    {
        $this->merchantUser->delete();

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginReturnsCorrectExpiryTime(): void
    {
        $before = now()->timestamp;

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $after = now()->timestamp;
        $expiresAt = $response->json('expiresAt');
        $expectedExpiry = config('sanctum.expiration');

        $this->assertNotNull($expiresAt);
        $this->assertGreaterThanOrEqual($before + $expectedExpiry, $expiresAt);
        $this->assertLessThanOrEqual($after + $expectedExpiry, $expiresAt);
    }

    public function testCustomerTokenCannotAuthenticateAsMerchantUser(): void
    {
        $customerToken = \App\Models\User::factory()->create()->createToken(config('sanctum.token_name'))->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$customerToken}")
            ->postJson('/api/v1/merchant-auth/logout');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginUpdatesLastLoginAt(): void
    {
        $this->merchantUser->update(['last_login_at' => null]);

        $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $this->assertNotNull($this->merchantUser->fresh()->last_login_at);
    }

    public function testLoginIsRateLimited(): void
    {
        for ($i = 0; $i < config('rate_limiting.login.attempts'); $i++) {
            $this->postJson(self::merchantLoginEndpoint(), [
                'credential' => 'merchant@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'credential' => 'merchant@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
