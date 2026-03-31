<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Enums\MerchantUserStatus;
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

    public function testMerchantUserCanLoginWithValidCredentials(): void
    {
        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
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

    public function testLoginGeneratesValidToken(): void
    {
        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
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
                    'email' => 'owner@example.com',
                    'password' => 'WrongPassword123!@#',
                ],
                'expectedError' => [
                    'credentials' => [
                        'Invalid email or password',
                    ],
                ],
            ],
            'non-existent email' => [
                'payload' => [
                    'email' => 'nobody@example.com',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'credentials' => [
                        'Invalid email or password',
                    ],
                ],
            ],
            'missing email' => [
                'payload' => [
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'email' => [
                        'The email field is required.',
                    ],
                ],
            ],
            'missing password' => [
                'payload' => [
                    'email' => 'owner@example.com',
                ],
                'expectedError' => [
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ],
            'invalid email format' => [
                'payload' => [
                    'email' => 'not-an-email',
                    'password' => self::TEST_PASSWORD,
                ],
                'expectedError' => [
                    'email' => [
                        'The email field must be a valid email address.',
                    ],
                ],
            ],
            'empty credentials' => [
                'payload' => [],
                'expectedError' => [
                    'email' => [
                        'The email field is required.',
                    ],
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('providesNonActiveMerchantUserStatuses')]
    public function testNonActiveMerchantUserCannotLogin(MerchantUserStatus $status): void
    {
        $this->merchantUser->update([
            'status' => $status->value,
        ]);

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
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

    public function testSoftDeletedMerchantUserCannotLogin(): void
    {
        $this->merchantUser->delete();

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginReturnsCorrectExpiryTime(): void
    {
        $before = now()->timestamp;

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
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

    public function testLoginIsRateLimited(): void
    {
        for ($i = 0; $i < config('rate_limiting.login.attempts'); $i++) {
            $this->postJson(self::merchantLoginEndpoint(), [
                'email' => 'merchant@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => 'merchant@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
