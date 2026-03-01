<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Jobs\SendPasswordResetNotificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use AuthEndpoints;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake([SendPasswordResetNotificationJob::class]);
    }

    public function testUserCanRequestPasswordReset(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson(self::forgotPasswordEndpoint(), [
            'email' => $user->email,
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);

        Bus::assertDispatched(SendPasswordResetNotificationJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    public function testPasswordResetFailsWithInvalidEmailFormat(): void
    {
        $response = $this->postJson(self::forgotPasswordEndpoint(), [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);

        Bus::assertNotDispatched(SendPasswordResetNotificationJob::class);
    }

    public function testPasswordResetDoesNotRevealNonExistentEmail(): void
    {
        $response = $this->postJson(self::forgotPasswordEndpoint(), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'If an account exists with that email, a password reset link has been sent.',
            ]);

        Bus::assertNotDispatched(SendPasswordResetNotificationJob::class);
    }

    public function testPasswordResetRequiresEmail(): void
    {
        $response = $this->postJson(self::forgotPasswordEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);

        Bus::assertNotDispatched(SendPasswordResetNotificationJob::class);
    }

    public function testMultipleUsersCanRequestPasswordResetIndependently(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        $this->postJson(self::forgotPasswordEndpoint(), ['email' => 'user1@example.com'])
            ->assertStatus(Response::HTTP_OK);

        $this->postJson(self::forgotPasswordEndpoint(), ['email' => 'user2@example.com'])
            ->assertStatus(Response::HTTP_OK);

        Bus::assertDispatched(SendPasswordResetNotificationJob::class, function ($job) use ($user1) {
            return $job->user->id === $user1->id;
        });

        Bus::assertDispatched(SendPasswordResetNotificationJob::class, function ($job) use ($user2) {
            return $job->user->id === $user2->id;
        });

        Bus::assertDispatchedTimes(SendPasswordResetNotificationJob::class, 2);
    }
}
