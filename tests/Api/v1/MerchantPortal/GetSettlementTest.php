<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Settlement;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class GetSettlementTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;
    private MerchantUser $merchantUser;

    private const VALID_STRUCTURE = [
        'data' => [
            'id',
            'referenceCode',
            'periodStart',
            'periodEnd',
            'totalSales',
            'totalFees',
            'netAmount',
            'transactionCount',
            'status',
            'statusLabel',
            'bankName',
            'bankAccountNumber',
            'bankAccountName',
            'paymentReference',
            'paidAt',
            'createdAt',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testMerchantUserCanGetSettlement(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getSettlementEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonPath('data.id', $settlement->id);
    }

    public function testMerchantCannotGetAnotherMerchantsSettlement(): void
    {
        $otherSettlement = Settlement::factory()->for(Merchant::factory()->active())->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getSettlementEndpoint($otherSettlement->id));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundForNonExistentSettlement(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::getSettlementEndpoint('00000000-0000-0000-0000-000000000000'));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->getJson(self::getSettlementEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::getSettlementEndpoint($settlement->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
