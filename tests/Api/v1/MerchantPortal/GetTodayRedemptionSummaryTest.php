<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetTodayRedemptionSummaryTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;
    private MerchantUser $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testReturnsTodaySummary(): void
    {
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'amount' => 50.00,
            'redeemed_at' => now(),
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getTodayRedemptionSummaryEndpoint());
        
        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => ['totalAmount', 'count'],
            ])
            ->assertJsonPath('data.count', 3)
            ->assertJsonPath('data.totalAmount', 150);
    }

    public function testExcludesRedemptionsFromOtherDays(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'redeemed_at' => now(),
        ]);

        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'redeemed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.count', 2);
    }

    public function testExcludesPendingAndCancelledRedemptions(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'redeemed_at' => now(),
        ]);

        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::PENDING,
            'redeemed_at' => null,
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.count', 2);
    }

    public function testExcludesOtherMerchantsRedemptions(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'redeemed_at' => now(),
        ]);

        CreditTransaction::factory()->count(3)->redemption()
            ->for(Merchant::factory()->active()->create())
            ->create([
                'status' => TransactionStatus::COMPLETED,
                'redeemed_at' => now(),
            ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.count', 2);
    }

    public function testReturnsZeroWhenNoRedemptionsToday(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.count', 0)
            ->assertJsonPath('data.totalAmount', 0);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::getTodayRedemptionSummaryEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
