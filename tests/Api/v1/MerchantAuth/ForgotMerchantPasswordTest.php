<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Jobs\SendMerchantUserPasswordResetNotificationJob;
use App\Models\MerchantUser;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantAuthEndpoints;
use Tests\TestCase;

class ForgotMerchantPasswordTest extends TestCase
{
    use MerchantAuthEndpoints;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake([SendMerchantUserPasswordResetNotificationJob::class]);
    }

    public function testMerchantCanRequestPasswordReset(): void
    {
        $merchantUser = MerchantUser::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson(self::merchantForgotPasswordEndpoint(), [
            'email' => $merchantUser->email,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);

        Bus::assertDispatched(SendMerchantUserPasswordResetNotificationJob::class, function ($job) use ($merchantUser) {
            return $job->merchantUser->id === $merchantUser->id;
        });
    }

    public function testPasswordResetFailsWithInvalidEmailFormat(): void
    {
        $response = $this->postJson(self::merchantForgotPasswordEndpoint(), [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);

        Bus::assertNotDispatched(SendMerchantUserPasswordResetNotificationJob::class);
    }

    public function testPasswordResetDoesNotRevealNonExistentEmail(): void
    {
        $response = $this->postJson(self::merchantForgotPasswordEndpoint(), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);

        Bus::assertNotDispatched(SendMerchantUserPasswordResetNotificationJob::class);
    }

    public function testPasswordResetRequiresEmail(): void
    {
        $response = $this->postJson(self::merchantForgotPasswordEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);

        Bus::assertNotDispatched(SendMerchantUserPasswordResetNotificationJob::class);
    }

    public function testMultipleMerchantUsersCanRequestPasswordResetIndependently(): void
    {
        $merchantUser1 = MerchantUser::factory()->create(['email' => 'user1@example.com']);
        $merchantUser2 = MerchantUser::factory()->create(['email' => 'user2@example.com']);

        $this->postJson(self::merchantForgotPasswordEndpoint(), ['email' => 'user1@example.com'])
            ->assertStatus(Response::HTTP_OK);

        $this->postJson(self::merchantForgotPasswordEndpoint(), ['email' => 'user2@example.com'])
            ->assertStatus(Response::HTTP_OK);

        Bus::assertDispatched(SendMerchantUserPasswordResetNotificationJob::class, function ($job) use ($merchantUser1) {
            return $job->merchantUser->id === $merchantUser1->id;
        });

        Bus::assertDispatched(SendMerchantUserPasswordResetNotificationJob::class, function ($job) use ($merchantUser2) {
            return $job->merchantUser->id === $merchantUser2->id;
        });

        Bus::assertDispatchedTimes(SendMerchantUserPasswordResetNotificationJob::class, 2);
    }

    public function testForgotPasswordIsRateLimited(): void
    {
        $email = 'merchant@example.com';

        for ($i = 0; $i < config('rate_limiting.forgot_password.attempts'); $i++) {
            $this->postJson(self::merchantForgotPasswordEndpoint(), ['email' => $email]);
        }

        $response = $this->postJson(self::merchantForgotPasswordEndpoint(), ['email' => $email]);

        $response->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
