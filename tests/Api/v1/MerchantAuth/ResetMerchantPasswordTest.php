<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Models\MerchantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantAuthEndpoints;
use Tests\TestCase;

class ResetMerchantPasswordTest extends TestCase
{
    use MerchantAuthEndpoints;

    public function testOwnerCanResetPasswordWithValidSignedUrl(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $url = $this->merchantResetPasswordUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Password has been reset successfully.',
            ]);

        $merchantUser->refresh();

        $this->assertTrue(Hash::check('NewPassword123!@#', $merchantUser->password));
        $this->assertFalse(Hash::check('OldPassword123!@#', $merchantUser->password));
    }

    public function testPasswordResetFailsWithTamperedSignature(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $url = $this->merchantResetPasswordUrl($merchantUser);
        $tamperedUrl = "{$url}&tampered=true";

        $response = $this->postJson($tamperedUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $merchantUser->refresh();
        $this->assertTrue(Hash::check('OldPassword123!@#', $merchantUser->password));
    }

    public function testPasswordResetFailsWithExpiredUrl(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create([
            'password' => Hash::make('OldPassword123!@#'),
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'merchant.password.reset',
            now()->subHour(),
            ['id' => $merchantUser->getKey()]
        );

        $response = $this->postJson($expiredUrl, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $merchantUser->refresh();
        $this->assertTrue(Hash::check('OldPassword123!@#', $merchantUser->password));
    }

    public function testPasswordResetFailsForStaffAccounts(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->create();

        $url = $this->merchantResetPasswordUrl($staff);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid password reset link.',
            ]);
    }

    public function testPasswordResetFailsIfUserDoesNotExist(): void
    {
        $url = URL::temporarySignedRoute(
            'merchant.password.reset',
            now()->addHour(),
            ['id' => Str::uuid()->toString()]
        );

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'NewPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'Invalid password reset link.',
            ]);
    }

    public function testPasswordResetFailsWithMissingPassword(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create();

        $url = $this->merchantResetPasswordUrl($merchantUser);

        $response = $this->postJson($url, []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }

    public function testPasswordResetFailsWithPasswordMismatch(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()->create();

        $url = $this->merchantResetPasswordUrl($merchantUser);

        $response = $this->postJson($url, [
            'password' => 'NewPassword123!@#',
            'password_confirmation' => 'DifferentPassword123!@#',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['password']);
    }
}
