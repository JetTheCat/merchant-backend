<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    use UserEndpoints;

    private const VALID_USER_RESPONSE_FIELDS = [
        'id',
        'firstName',
        'lastName',
        'email',
        'emailVerifiedAt',
        'createdAt',
        'updatedAt',
    ];

    public function testAuthenticatedUserCanUpdateTheirProfile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $payload = [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data' => self::VALID_USER_RESPONSE_FIELDS,
            ])
            ->assertJson([
                'message' => 'Profile updated successfully',
                'data' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                    'email' => 'jane@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);
    }

    public function testUserCanUpdateName(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
            ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'firstName' => 'Jane',
                    'lastName' => 'Smith',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function testUserCanUpdateOnlyEmail(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $payload = [
            'email' => 'newemail@example.com',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'email' => 'newemail@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    public function testUserCanKeepSameEmailWhenUpdating(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $payload = [
            'email' => 'john@example.com',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'email' => 'john@example.com',
                ],
            ]);
    }

    public function testUserCannotUpdateToExistingEmail(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $payload = [
            'email' => 'existing@example.com',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
            ->assertJson([
                'errors' => [
                    'email' => [
                        'The email has already been taken.',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'john@example.com',
        ]);
    }

    public function testUnauthenticatedUserCannotUpdateProfile(): void
    {
        $payload = [
            'firstName' => 'Jane',
        ];

        $response = $this->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[DataProvider('invalidUpdateData')]
    public function testUserCannotUpdateWithInvalidData(array $payload, array $expectedErrors): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);
    }

    public static function invalidUpdateData(): array
    {
        return [
            'invalid email format' => [
                'payload' => [
                    'email' => 'invalid-email',
                ],
                'expectedErrors' => ['email'],
            ],
            'empty first name' => [
                'payload' => [
                    'firstName' => '',
                ],
                'expectedErrors' => ['firstName'],
            ],
            'empty last name' => [
                'payload' => [
                    'lastName' => '',
                ],
                'expectedErrors' => ['lastName'],
            ],
            'empty email' => [
                'payload' => [
                    'email' => '',
                ],
                'expectedErrors' => ['email'],
            ],
            'first name too long' => [
                'payload' => [
                    'firstName' => str_repeat('a', 256),
                ],
                'expectedErrors' => ['firstName'],
            ],
            'last name too long' => [
                'payload' => [
                    'lastName' => str_repeat('a', 256),
                ],
                'expectedErrors' => ['lastName'],
            ],
            'email too long' => [
                'payload' => [
                    'email' => str_repeat('a', 250).'@test.com',
                ],
                'expectedErrors' => ['email'],
            ],
        ];
    }

    public function testUpdateResponseDoesNotExposePassword(): void
    {
        $user = User::factory()->create();

        $payload = [
            'firstName' => 'Jane',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonMissing(['password']);
    }

    public function testUserCannotUpdatePasswordThroughProfileUpdate(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!@#',
        ]);

        $oldPasswordHash = $user->password;

        $payload = [
            'password' => 'NewPassword123!@#',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK);

        $user->refresh();

        $this->assertEquals($oldPasswordHash, $user->password);
    }

    public function testUserCannotUpdateStatus(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $payload = [
            'status' => 'suspended',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updateUserEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK);

        $user->refresh();

        $this->assertEquals('active', $user->status->value);
    }
}
