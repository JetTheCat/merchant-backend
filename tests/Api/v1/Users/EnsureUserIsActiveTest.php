<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\User;
use App\UserStatus;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class EnsureUserIsActiveTest extends TestCase
{
    use UserEndpoints;

    public function testActiveUserCanAccessProtectedRoutes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::ACTIVE]);

        $this->actingAs($user)
            ->getJson(self::getUserEndpoint())
            ->assertStatus(Response::HTTP_OK);
    }

    public function testSuspendedUserCannotAccessProtectedRoutes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::SUSPENDED]);

        $this->actingAs($user)
            ->getJson(self::getUserEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }

    public function testBannedUserCannotAccessProtectedRoutes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::BANNED]);

        $this->actingAs($user)
            ->getJson(self::getUserEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJsonPath('message', 'Your account has been suspended. Please contact support.');
    }

    public function testMiddlewareEnforcedAcrossUserRoutes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::SUSPENDED]);

        $this->actingAs($user)
            ->getJson(self::listUserCreditsEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
