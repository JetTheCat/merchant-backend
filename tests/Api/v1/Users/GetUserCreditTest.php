<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\Merchant;
use App\Models\User;
use App\Models\UserCredit;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetUserCreditTest extends TestCase
{
    use MerchantPortalEndpoints;

    private User $user;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->create();
    }

    public function testUserCanGetCreditBalanceForMerchant(): void
    {
        $credit = UserCredit::factory()
            ->for($this->user)
            ->for($this->merchant)
            ->withBalance(150)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getUserCreditEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'merchantId',
                    'merchant',
                    'balance',
                    'totalPurchased',
                    'totalRedeemed',
                    'lastPurchaseAt',
                    'lastRedemptionAt',
                    'createdAt',
                    'updatedAt',
                ],
            ])
            ->assertJson([
                'data' => [
                    'merchantId' => $this->merchant->id,
                    'balance' => '150.00',
                ],
            ]);
    }

    public function testReturnsNotFoundWhenNoWalletForMerchant(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getUserCreditEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testCannotAccessAnotherUsersCredit(): void
    {
        $otherUser = User::factory()->create();

        UserCredit::factory()
            ->for($otherUser)
            ->for($this->merchant)
            ->withBalance(100)
            ->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getUserCreditEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentMerchant(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getUserCreditEndpoint(Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getUserCreditEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
