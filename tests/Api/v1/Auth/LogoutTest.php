<?php

declare(strict_types=1);

namespace Tests\Api\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\AuthEndpoints;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use AuthEndpoints;

    private const TEST_PASSWORD = 'SecurePass123!@#';
    private const TEST_EMAIL = 'jet@example.com';

    private User $user;
    private string $token;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => self::TEST_EMAIL,
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $loginResponse = $this->postJson(self::loginEndpoint(), [
            'email' => self::TEST_EMAIL,
            'password' => self::TEST_PASSWORD,
        ]);

        $this->token = $loginResponse->json('token');
    }

    public function testUserCanLogoutSuccessfully(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $this->assertCount(1, $this->user->tokens);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_OK);

        $this->user->refresh();
        $this->assertCount(0, $this->user->tokens);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function testRevokedTokenCannotBeUsedAfterLogout(): void
    {
        $logoutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::logoutEndpoint());

        $logoutResponse->assertStatus(Response::HTTP_OK);

        Auth::forgetGuards();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testUserCannotLogoutWithoutAuthentication(): void
    {
        $response = $this->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testUserCannotLogoutWithInvalidToken(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-here')
            ->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutOnlyRevokesCurrentTokenNotAllTokens(): void
    {
        $this->user->createToken(config('sanctum.token_name'));

        $this->assertCount(2, $this->user->tokens);

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::logoutEndpoint());

        $logoutResponse->assertStatus(Response::HTTP_OK);

        $this->user->refresh();
        $this->assertCount(1, $this->user->tokens);
    }

    public function testLogoutWithExpiredToken(): void
    {
        $expiredToken = $this->user->createToken(
            name: config('sanctum.token_name'),
            abilities: ['*'],
            expiresAt: now()->subHour()
        );

        $response = $this->withHeader('Authorization', "Bearer {$expiredToken->plainTextToken}")
            ->postJson(self::logoutEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
