<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantEndpoints;
use Tests\Constants\Guards;
use Tests\TestCase;

class ListMerchantCreditPackagesTest extends TestCase
{
    use MerchantEndpoints;

    private Merchant $merchant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function testCustomerCanListActivePackagesForMerchant(): void
    {
        CreditPackage::factory()->count(3)->for($this->merchant)->active()->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'merchantId',
                        'name',
                        'description',
                        'creditAmount',
                        'sellingPrice',
                        'discountPercentage',
                        'isActive',
                        'purchaseLimitPerUser',
                        'totalQuantity',
                        'soldQuantity',
                        'createdAt',
                        'updatedAt',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function testInactivePackagesAreNotReturned(): void
    {
        CreditPackage::factory()->count(2)->for($this->merchant)->active()->create();
        CreditPackage::factory()->count(3)->for($this->merchant)->inactive()->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testOnlyPackagesForRequestedMerchantAreReturned(): void
    {
        CreditPackage::factory()->count(2)->for($this->merchant)->active()->create();
        CreditPackage::factory()->count(3)->for(Merchant::factory()->create())->active()->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testSoldOutPackagesAreExcluded(): void
    {
        CreditPackage::factory()->for($this->merchant)->active()->create([
            'total_quantity' => 10,
            'sold_quantity' => 5,
        ]);
        CreditPackage::factory()->for($this->merchant)->active()->create([
            'total_quantity' => 2,
            'sold_quantity' => 2,
        ]);

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testPackagesWithNoQuantityLimitAreAlwaysIncluded(): void
    {
        CreditPackage::factory()->count(3)->for($this->merchant)->active()->create([
            'total_quantity' => null,
        ]);

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');
    }

    public function testPackagesWhereUserReachedPurchaseLimitAreExcluded(): void
    {
        $availablePackage = CreditPackage::factory()->for($this->merchant)->active()->create([
            'purchase_limit_per_user' => 2,
        ]);
        $limitReachedPackage = CreditPackage::factory()->for($this->merchant)->active()->create([
            'purchase_limit_per_user' => 1,
        ]);

        CreditTransaction::factory()
            ->purchase()
            ->completed()
            ->for($this->user)
            ->for($limitReachedPackage)
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $availablePackage->id);
    }

    public function testFailedPurchasesDoNotCountTowardsPurchaseLimitFilter(): void
    {
        $package = CreditPackage::factory()->for($this->merchant)->active()->create([
            'purchase_limit_per_user' => 1,
        ]);

        CreditTransaction::factory()
            ->purchase()
            ->failed()
            ->for($this->user)
            ->for($package)
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testPurchaseLimitFilterIsPerUser(): void
    {
        $package = CreditPackage::factory()->for($this->merchant)->active()->create([
            'purchase_limit_per_user' => 1,
        ]);

        $otherUser = User::factory()->create();

        CreditTransaction::factory()
            ->purchase()
            ->completed()
            ->for($otherUser)
            ->for($package)
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');
    }

    public function testReturnsNotFoundForPausedMerchant(): void
    {
        $pausedMerchant = Merchant::factory()->active()->create([
            'credit_sales_paused_at' => now(),
        ]);

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $pausedMerchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForInactiveMerchant(): void
    {
        $inactiveMerchant = Merchant::factory()->create(['status' => \App\Enums\MerchantStatus::SUSPENDED]);

        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $inactiveMerchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentMerchant(): void
    {
        $response = $this->actingAs($this->user, Guards::CUSTOMER)
            ->getJson(self::listMerchantCreditPackagesEndpoint(\Illuminate\Support\Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
