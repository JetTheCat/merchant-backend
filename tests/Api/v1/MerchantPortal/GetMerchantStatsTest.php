<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetMerchantStatsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private const VALID_STRUCTURE = [
        'data' => [
            'todayRedemptions' => ['count', 'totalAmount'],
            'allTimeRevenue',
            'topPackages',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testReturnsCorrectStructure(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE);
    }

    public function testReturnsZerosWhenNoTransactions(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.todayRedemptions.count', 0)
            ->assertJsonPath('data.todayRedemptions.totalAmount', 0)
            ->assertJsonPath('data.allTimeRevenue', 0)
            ->assertJsonPath('data.topPackages', []);
    }

    public function testTodayRedemptionsCountAndAmountAreAccurate(): void
    {
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'amount' => 50.00,
            'redeemed_at' => now(),
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.todayRedemptions.count', 3)
            ->assertJsonPath('data.todayRedemptions.totalAmount', 150);
    }

    public function testTodayRedemptionsExcludesOtherDays(): void
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
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertJsonPath('data.todayRedemptions.count', 2);
    }

    public function testTodayRedemptionsExcludesNonCompletedRedemptions(): void
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
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertJsonPath('data.todayRedemptions.count', 2);
    }

    public function testAllTimeRevenueOnlySumsCompletedPurchases(): void
    {
        $package = CreditPackage::factory()->active()->for($this->merchant)->create();

        CreditTransaction::factory()->count(3)->purchase()->for($this->merchant)->create([
            'credit_package_id' => $package->id,
            'paid_amount' => 100.00,
        ]);

        // Pending purchase — should be excluded
        CreditTransaction::factory()->purchase()->pending()->for($this->merchant)->create([
            'credit_package_id' => $package->id,
            'paid_amount' => 100.00,
        ]);

        // Redemption — should be excluded
        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'amount' => 50.00,
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertJsonPath('data.allTimeRevenue', 300);
    }

    public function testTopPackagesAreOrderedByRevenueDescending(): void
    {
        $highPackage = CreditPackage::factory()->active()->for($this->merchant)->create(['name' => 'Gold']);
        $lowPackage = CreditPackage::factory()->active()->for($this->merchant)->create(['name' => 'Silver']);

        CreditTransaction::factory()->count(2)->purchase()->for($this->merchant)->create([
            'credit_package_id' => $highPackage->id,
            'paid_amount' => 200.00,
        ]);

        CreditTransaction::factory()->purchase()->for($this->merchant)->create([
            'credit_package_id' => $lowPackage->id,
            'paid_amount' => 50.00,
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.topPackages.0.name', 'Gold')
            ->assertJsonPath('data.topPackages.0.totalRevenue', 400)
            ->assertJsonPath('data.topPackages.0.purchaseCount', 2)
            ->assertJsonPath('data.topPackages.1.name', 'Silver')
            ->assertJsonPath('data.topPackages.1.totalRevenue', 50);
    }

    public function testTopPackagesLimitedToFive(): void
    {
        CreditPackage::factory()->count(7)->active()->for($this->merchant)
            ->create()
            ->each(function (CreditPackage $package) {
                CreditTransaction::factory()->purchase()->for($this->merchant)->create([
                    'credit_package_id' => $package->id,
                    'paid_amount' => 10.00,
                ]);
            });

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(5, 'data.topPackages');
    }

    public function testDoesNotLeakOtherMerchantData(): void
    {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherPackage = CreditPackage::factory()->active()->for($otherMerchant)->create();

        CreditTransaction::factory()->count(3)->redemption()->for($otherMerchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'amount' => 50.00,
            'redeemed_at' => now(),
        ]);

        CreditTransaction::factory()->count(3)->purchase()->for($otherMerchant)->create([
            'credit_package_id' => $otherPackage->id,
            'paid_amount' => 100.00,
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.todayRedemptions.count', 0)
            ->assertJsonPath('data.allTimeRevenue', 0)
            ->assertJsonPath('data.topPackages', []);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotAccessMerchantStats(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::getMerchantStatsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
