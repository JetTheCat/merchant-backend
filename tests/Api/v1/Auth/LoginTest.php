<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use AuthEndpoints;

    private const TEST_PASSWORD = 'SecurePass123!@#';

    private const TEST_EMAIL = 'jet@example.com';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => self::TEST_EMAIL,
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);
    }

    public function testUserCanLoginWithValidCredentials(): void
    {
        $payload = [
            'email' => $this->user->email,
            'password' => self::TEST_PASSWORD,
        ];

        $jsonResponse = $this->postJson(self::loginEndpoint(), $payload);

        $jsonResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'token',
                'expiresAt',
                'user' => [
                    'id',
                    'firstName',
                    'lastName',
                    'email',
                    'emailVerifiedAt',
                    'createdAt',
                    'updatedAt',
                ],
            ])
            ->assertJson([
                'user' => [
                    'email' => 'jet@example.com',
                ],
            ]);

        $this->assertNotNull($jsonResponse->json('token'));
        $this->assertIsInt($jsonResponse->json('expiresAt'));
    }

    #[DataProvider('invalidLoginData')]
    public function testUserCannotLoginWithInvalidCredentials(array $payload, array $expectedError): void
    {
        $jsonResponse = $this->postJson(self::loginEndpoint(), $payload);

        $jsonResponse->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJson([
                'errors' => $expectedError,
            ]);
    }

    public static function invalidLoginData(): array
    {
        return [
            'wrong password' => [
                'payload' => [
                    'email' => 'jet@example.com',
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
                    'email' => 'nonexistent@example.com',
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
                    'email' => 'jet@example.com',
                ],
                'expectedError' => [
                    'password' => [
                        'The password field is required.',
                    ],
                ],
            ],
            'invalid email format' => [
                'payload' => [
                    'email' => 'invalid-email',
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

    public function testLoginGeneratesValidToken(): void
    {
        $loginResponse = $this->postJson(self::loginEndpoint(), [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $loginResponse->assertStatus(Response::HTTP_OK);

        $token = $loginResponse->json('token');

        $this->assertNotNull($token);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => config('sanctum.token_name'),
        ]);

        $this->assertCount(1, $this->user->tokens);
    }

    public function testUserCannotLoginWithSoftDeletedAccount(): void
    {
        $this->user->delete();

        $jsonResponse = $this->postJson(self::loginEndpoint(), [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $jsonResponse->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginReturnsCorrectExpiryTime(): void
    {
        $beforeLogin = now()->timestamp;

        $jsonResponse = $this->postJson(self::loginEndpoint(), [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $afterLogin = now()->timestamp;
        $expiresAt = $jsonResponse->json('expiresAt');
        $expectedExpiry = config('sanctum.expiration');

        $this->assertNotNull($expiresAt);
        $this->assertGreaterThanOrEqual($beforeLogin + $expectedExpiry, $expiresAt);
        $this->assertLessThanOrEqual($afterLogin + $expectedExpiry, $expiresAt);
    }
}
