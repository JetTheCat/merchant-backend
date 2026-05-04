<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Models\CreditPackage;
use App\Models\Merchant;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantCreditPackagesTest extends TestCase
{
    use MerchantPortalEndpoints;

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

        $response = $this->actingAs($this->user, 'sanctum')
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

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testOnlyPackagesForRequestedMerchantAreReturned(): void
    {
        CreditPackage::factory()->count(2)->for($this->merchant)->active()->create();
        CreditPackage::factory()->count(3)->for(Merchant::factory()->create())->active()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testReturnsNotFoundForInactiveMerchant(): void
    {
        $inactiveMerchant = Merchant::factory()->create(['status' => \App\Enums\MerchantStatus::SUSPENDED]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listMerchantCreditPackagesEndpoint((string) $inactiveMerchant->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentMerchant(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listMerchantCreditPackagesEndpoint(\Illuminate\Support\Str::uuid()->toString()));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantCreditPackagesEndpoint((string) $this->merchant->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
