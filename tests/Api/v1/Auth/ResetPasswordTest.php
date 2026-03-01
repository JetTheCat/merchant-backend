<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use AuthEndpoints;

    public function testUserCanResetPasswordWithValidSignedUrl(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $resetUrl = $this->generateResetPasswordUrl($user);

        $response = $this->postJson($resetUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password has been reset successfully.',
            ]);

        $user->refresh();

        $this->assertTrue(Hash::check('NewPassword123!@#', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!@#', $user->password));
    }

    public function testPasswordResetFailsWithTamperedUserId(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $otherUser = User::factory()->create([
            'password' => Hash::make('OtherPassword123!@#'),
        ]);

        $resetUrl = $this->generateResetPasswordUrl($user);

        $tamperedUrl = str_replace(
            "/password/reset/{$user->id}",
            "/password/reset/{$otherUser->id}",
            $resetUrl
        );

        $response = $this->postJson($tamperedUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $user->refresh();
        $otherUser->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
        $this->assertTrue(Hash::check('OtherPassword123!@#', $otherUser->password));
    }

    public function testPasswordResetFailsWithTamperedSignature(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $resetUrl = $this->generateResetPasswordUrl($user);

        $tamperedUrl = $resetUrl.'&tampered=true';

        $response = $this->postJson($tamperedUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
    }

    public function testPasswordResetFailsIfSignatureIsExpired(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'password.reset',
            now()->subHour(),
            [
                'id' => $user->id,
            ]
        );

        $response = $this->postJson($expiredUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $user->refresh();

        $this->assertTrue(Hash::check('OldPassword123!@#', $user->password));
    }

    public function testPasswordResetFailsIfUserDoesNotExist(): void
    {
        $nonExistentId = Str::uuid()->toString();

        $resetUrl = URL::temporarySignedRoute(
            'password.reset',
            now()->addHour(),
            [
                'id' => $nonExistentId,
            ]
        );

        $response = $this->postJson($resetUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid password reset link.',
            ]);
    }

    private function generateResetPasswordUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'password.reset',
            now()->addHour(),
            [
                'id' => $user->id,
            ]
        );
    }
}
