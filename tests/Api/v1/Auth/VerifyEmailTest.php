<?php

declare(strict_types=1);

namespace Tests\Api\v1\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([Verified::class]);
    }

    public function testUserCanVerifyEmail(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = $this->generateVerificationUrl($user);

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Email verified successfully.',
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);

        Event::assertDispatchedTimes(Verified::class);
        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function testAlreadyVerifiedEmailReturnsAppropriateMessage(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verificationUrl = $this->generateVerificationUrl($user);

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Email already verified.',
            ]);

        Event::assertNotDispatched(Verified::class);
    }

    public function testVerificationFailsWithInvalidSignature(): void
    {
        $user = User::factory()->unverified()->create();

        $invalidUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('wrong-email@example.com'),
            ]
        );

        $response = $this->getJson($invalidUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNull($user->fresh()->email_verified_at);

        Event::assertNotDispatched(Verified::class);
    }

    public function testVerificationFailsWithExpiredSignature(): void
    {
        $user = User::factory()->unverified()->create();

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(10),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->getJson($expiredUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNull($user->fresh()->email_verified_at);

        Event::assertNotDispatched(Verified::class);
    }

    public function testVerificationFailsWithWrongUserId(): void
    {
        $user = User::factory()->unverified()->create();
        $otherUser = User::factory()->unverified()->create();

        $verificationUrl = $this->generateVerificationUrl($otherUser);

        $tamperedUrl = str_replace(
            $otherUser->id,
            $user->id,
            $verificationUrl
        );

        $response = $this->getJson($tamperedUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($otherUser->fresh()->email_verified_at);

        Event::assertNotDispatched(Verified::class);
    }

    public function testVerificationFailsWithTamperedUrl(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = $this->generateVerificationUrl($user);

        $tamperedUrl = str_replace(
            "/email/verify/{$user->id}/",
            '/email/verify/999/',
            $verificationUrl
        );

        $response = $this->getJson($tamperedUrl);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertNull($user->fresh()->email_verified_at);

        Event::assertNotDispatched(Verified::class);
    }

    public function testVerifiedEventIsNotDispatchedForAlreadyVerifiedUser(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $verificationUrl = $this->generateVerificationUrl($user);

        $this->getJson($verificationUrl);

        Event::assertNotDispatched(Verified::class);
    }

    private function generateVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );
    }
}
