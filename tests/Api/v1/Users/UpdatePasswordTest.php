<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class UpdatePasswordTest extends TestCase
{
    use UserEndpoints;

    public function testAuthenticatedUserCanUpdatePassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $payload = [
            'currentPassword' => 'OldPassword123!@#',
            'password' => 'NewPassword123!@#',
            'passwordConfirmation' => 'NewPassword123!@#',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password updated successfully.',
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword123!@#', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!@#', $user->password));
    }

    public function testUnauthenticatedUserCannotUpdatePassword(): void
    {
        $payload = [
            'currentPassword' => 'OldPassword123!@#',
            'password' => 'NewPassword123!@#',
            'passwordConfirmation' => 'NewPassword123!@#',
        ];

        $response = $this->patchJson(self::updatePasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testPasswordUpdateFailsWithIncorrectCurrentPassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $payload = [
            'currentPassword' => 'WrongPassword123!@#',
            'password' => 'NewPassword123!@#',
            'passwordConfirmation' => 'NewPassword123!@#',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currentPassword']);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
    }

    public function testPasswordUpdateFailsWithMismatchedConfirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $payload = [
            'currentPassword' => 'OldPassword123!@#',
            'password' => 'NewPassword123!@#',
            'passwordConfirmation' => 'DifferentPassword123!@#',
        ];

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
    }

    #[DataProvider('invalidPasswordData')]
    public function testPasswordUpdateFailsWithInvalidPassword(array $payload, array $expectedErrors): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors($expectedErrors);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
    }

    public static function invalidPasswordData(): array
    {
        return [
            'missing current password' => [
                'payload' => [
                    'password' => 'NewPassword123!@#',
                    'passwordConfirmation' => 'NewPassword123!@#',
                ],
                'expectedErrors' => ['currentPassword'],
            ],
            'missing new password' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'passwordConfirmation' => 'NewPassword123!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'missing password confirmation' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'NewPassword123!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'password too short' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'Short1!',
                    'passwordConfirmation' => 'Short1!',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without uppercase' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'newpassword123!@#',
                    'passwordConfirmation' => 'newpassword123!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without lowercase' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'NEWPASSWORD123!@#',
                    'passwordConfirmation' => 'NEWPASSWORD123!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without numbers' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'NewPassword!@#',
                    'passwordConfirmation' => 'NewPassword!@#',
                ],
                'expectedErrors' => ['password'],
            ],
            'password without symbols' => [
                'payload' => [
                    'currentPassword' => 'OldPassword123!@#',
                    'password' => 'NewPassword123',
                    'passwordConfirmation' => 'NewPassword123',
                ],
                'expectedErrors' => ['password'],
            ],
        ];
    }

    public function testPasswordUpdateRequiresAllFields(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currentPassword', 'password']);
    }

    public function testMultipleUsersCanUpdatePasswordsIndependently(): void
    {
        $user1 = User::factory()->create([
            'password' => Hash::make('User1Password123!@#'),
        ]);

        $user2 = User::factory()->create([
            'password' => Hash::make('User2Password123!@#'),
        ]);

        $this->actingAs($user1)
            ->patchJson(self::updatePasswordEndpoint(), [
                'currentPassword' => 'User1Password123!@#',
                'password' => 'NewUser1Password123!@#',
                'passwordConfirmation' => 'NewUser1Password123!@#',
            ])
            ->assertStatus(Response::HTTP_OK);

        $this->actingAs($user2)
            ->patchJson(self::updatePasswordEndpoint(), [
                'currentPassword' => 'User2Password123!@#',
                'password' => 'NewUser2Password123!@#',
                'passwordConfirmation' => 'NewUser2Password123!@#',
            ])
            ->assertStatus(Response::HTTP_OK);

        $user1->refresh();
        $user2->refresh();

        $this->assertTrue(Hash::check('NewUser1Password123!@#', $user1->password));
        $this->assertTrue(Hash::check('NewUser2Password123!@#', $user2->password));
    }

    public function testOldPasswordCannotBeReusedAfterUpdate(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), [
                'currentPassword' => 'OldPassword123!@#',
                'password' => 'NewPassword123!@#',
                'passwordConfirmation' => 'NewPassword123!@#',
            ])
            ->assertStatus(Response::HTTP_OK);

        $user->refresh();

        $response = $this->actingAs($user)
            ->patchJson(self::updatePasswordEndpoint(), [
                'currentPassword' => 'OldPassword123!@#',
                'password' => 'AnotherPassword123!@#',
                'passwordConfirmation' => 'AnotherPassword123!@#',
            ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['currentPassword']);
    }
}
