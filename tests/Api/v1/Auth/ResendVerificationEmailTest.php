<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Jobs\SendEmailVerificationJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class ResendVerificationEmailTest extends TestCase
{
    use AuthEndpoints;

    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake(SendEmailVerificationJob::class);
    }

    public function testAuthenticatedUserCanResendVerificationEmail(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->postJson(self::resendVerificationEmailEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Verification email sent.',
            ]);

        Bus::assertDispatchedTimes(SendEmailVerificationJob::class, 1);

        Bus::assertDispatched(
            SendEmailVerificationJob::class,
            fn (SendEmailVerificationJob $job) => $job->user->id === $user->id
        );
    }

    public function testUnauthenticatedUserCannotResendVerificationEmail(): void
    {
        $response = $this->postJson(self::resendVerificationEmailEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        Bus::assertNotDispatched(SendEmailVerificationJob::class);
    }

    public function testAlreadyVerifiedUserCannotResendVerificationEmail(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(self::resendVerificationEmailEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Email already verified.',
            ]);

        Bus::assertNotDispatched(SendEmailVerificationJob::class);
    }

    public function testMultipleUsersCanResendIndependently(): void
    {
        $user1 = User::factory()->unverified()->create();
        $user2 = User::factory()->unverified()->create();

        $this->actingAs($user1)
            ->postJson(self::resendVerificationEmailEndpoint());

        $this->actingAs($user2)
            ->postJson(self::resendVerificationEmailEndpoint());

        Bus::assertDispatchedTimes(SendEmailVerificationJob::class, 2);

        Bus::assertDispatched(
            SendEmailVerificationJob::class,
            fn (SendEmailVerificationJob $job) => $job->user->id === $user1->id
        );

        Bus::assertDispatched(
            SendEmailVerificationJob::class,
            fn (SendEmailVerificationJob $job) => $job->user->id === $user2->id
        );
    }
}
