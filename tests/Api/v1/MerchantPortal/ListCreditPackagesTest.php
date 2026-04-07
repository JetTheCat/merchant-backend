<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\CreditPackage;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListCreditPackagesTest extends TestCase
{
    use MerchantPortalEndpoints;

    private MerchantUser $owner;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->create();
        $this->owner = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testOwnerCanListCreditPackages(): void
    {
        CreditPackage::factory()->count(3)->for($this->merchant)->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listCreditPackagesEndpoint());

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
                        'validityDays',
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

    public function testStaffCanListCreditPackages(): void
    {
        $staff = MerchantUser::factory()->staff()->active()->for($this->merchant)->create();

        CreditPackage::factory()->count(2)->for($this->merchant)->create();

        $response = $this->actingAs($staff, self::MERCHANT_USER_GUARD)
            ->getJson(self::listCreditPackagesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testOnlyOwnMerchantPackagesAreReturned(): void
    {
        CreditPackage::factory()->count(2)->for($this->merchant)->create();
        CreditPackage::factory()->count(3)->for(Merchant::factory()->create())->create();

        $response = $this->actingAs($this->owner, self::MERCHANT_USER_GUARD)
            ->getJson(self::listCreditPackagesEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testListCreditPackagesRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listCreditPackagesEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testCustomerCannotListMerchantPackages(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::listCreditPackagesEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
