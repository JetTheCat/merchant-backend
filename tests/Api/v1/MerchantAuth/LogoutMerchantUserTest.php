<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantAuth;

use App\Models\MerchantUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantAuthEndpoints;
use Tests\TestCase;

class LogoutMerchantUserTest extends TestCase
{
    use MerchantAuthEndpoints;

    private const TEST_PASSWORD = 'SecurePass123!@#';

    private MerchantUser $merchantUser;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchantUser = MerchantUser::factory()->owner()->active()->create([
            'password' => Hash::make(self::TEST_PASSWORD),
        ]);

        $loginResponse = $this->postJson(self::merchantLoginEndpoint(), [
            'email' => $this->merchantUser->email,
            'password' => self::TEST_PASSWORD,
        ]);

        $this->token = $loginResponse->json('token');
    }

    public function testMerchantUserCanLogoutSuccessfully(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::merchantLogoutEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Logged out successfully',
            ]);
    }

    public function testLogoutRevokesCurrentToken(): void
    {
        $this->assertCount(1, $this->merchantUser->tokens);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::merchantLogoutEndpoint());

        $this->merchantUser->refresh();
        $this->assertCount(0, $this->merchantUser->tokens);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->merchantUser->id,
            'tokenable_type' => MerchantUser::class,
        ]);
    }

    public function testRevokedTokenCannotBeUsedAfterLogout(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_OK);

        Auth::forgetGuards();

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutOnlyRevokesCurrentTokenNotAllTokens(): void
    {
        $this->merchantUser->createToken(config('sanctum.token_name'));

        $this->assertCount(2, $this->merchantUser->tokens);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_OK);

        $this->merchantUser->refresh();
        $this->assertCount(1, $this->merchantUser->tokens);
    }

    public function testMerchantUserCannotLogoutWithoutAuthentication(): void
    {
        $this->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotLogoutWithInvalidToken(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token-here')
            ->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutWithExpiredToken(): void
    {
        $expiredToken = $this->merchantUser->createToken(
            name: config('sanctum.token_name'),
            abilities: ['*'],
            expiresAt: now()->subHour()
        );

        $this->withHeader('Authorization', "Bearer {$expiredToken->plainTextToken}")
            ->postJson(self::merchantLogoutEndpoint())
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
