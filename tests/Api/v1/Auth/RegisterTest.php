<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use AuthEndpoints;

    private const VALID_PAYLOAD = [
        'firstName' => 'Jet',
        'lastName' => 'Lim',
        'email' => 'jet@example.com',
        'password' => 'SecurePass123!@#',
        'passwordConfirmation' => 'SecurePass123!@#',
        'phone' => '+60123456789',
    ];

    public function testUserCanRegisterWithValidData(): void
    {
        $jsonResponse = $this->postJson(self::registerEndpoint(), self::VALID_PAYLOAD);

        $jsonResponse->assertStatus(201)
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
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jet@example.com',
            'first_name' => 'Jet',
            'last_name' => 'Lim',
        ]);

        $this->assertNotNull($jsonResponse->json('token'));
        $this->assertIsInt($jsonResponse->json('expiresAt'));
    }

    public function testUserCannotRegisterWithDuplicateEmail(): void
    {
        User::factory()->create([
            'email' => self::VALID_PAYLOAD['email'],
        ]);

        $response = $this->postJson(self::registerEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function testUserCannotRegisterWithDuplicatePhone(): void
    {
        User::factory()->create([
            'phone' => self::VALID_PAYLOAD['phone'],
        ]);

        $response = $this->postJson(self::registerEndpoint(), self::VALID_PAYLOAD);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    #[DataProvider('invalidRegistrationData')]
    public function testUserCannotRegisterWithInvalidData(array $payload, array $expectedErrors): void
    {
        $response = $this->postJson(self::registerEndpoint(), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidRegistrationData(): array
    {
        return [
            'missing all mandatory fields' => [
                'payload' => [],
                'expectedErrors' => ['firstName', 'lastName', 'email', 'password'],
            ],
            'missing first name' => [
                'payload' => [
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                    'password' => 'SecurePass123!@#',
                    'passwordConfirmation' => 'SecurePass123!@#',
                ],
                'expectedErrors' => ['firstName'],
            ],
            'missing last name' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'email' => 'jet@example.com',
                    'password' => 'SecurePass123!@#',
                    'passwordConfirmation' => 'SecurePass123!@#',
                ],
                'expectedErrors' => ['lastName'],
            ],
            'invalid email format' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'invalid-email',
                    'password' => 'SecurePass123!@#',
                    'passwordConfirmation' => 'SecurePass123!@#',
                ],
                'expectedErrors' => ['email'],
            ],
            'weak password' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                    'password' => 'weak',
                    'passwordConfirmation' => 'weak',
                ],
                'expectedErrors' => ['password'],
            ],
            'mismatched password confirmation' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                    'password' => 'SecurePass123!@#',
                    'passwordConfirmation' => 'DifferentPass123!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'password too short' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                    'password' => 'Short1!',
                    'passwordConfirmation' => 'Short1!',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => [
                    'firstName' => 'Jet',
                    'lastName' => 'Lim',
                    'email' => 'jet@example.com',
                    'password' => 'NoSymbols123',
                    'passwordConfirmation' => 'NoSymbols123',
                ],
                'expectedErrors' => ['password'],
            ],
        ];
    }
}
