<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\SettlementStatus;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Settlement;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListSettlementsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private const VALID_STRUCTURE = [
        'data' => [
            '*' => [
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
        ],
        'meta' => ['perPage', 'nextCursor', 'prevCursor', 'hasMore'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->active()->for($this->merchant)->create();
    }

    public function testMerchantUserCanListSettlements(): void
    {
        Settlement::factory()->count(3)->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testOnlyOwnMerchantSettlementsAreReturned(): void
    {
        Settlement::factory()->count(2)->for($this->merchant)->create();
        Settlement::factory()->count(3)->for(Merchant::factory()->active()->create())->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByStatus(): void
    {
        Settlement::factory()->count(2)->for($this->merchant)->create(['status' => SettlementStatus::COMPLETED]);
        Settlement::factory()->count(3)->for($this->merchant)->create(['status' => SettlementStatus::PENDING]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint().'?status=completed');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateFrom(): void
    {
        Settlement::factory()->count(2)->for($this->merchant)->create(['period_start' => now()->subDays(3)]);
        Settlement::factory()->count(3)->for($this->merchant)->create(['period_start' => now()->subDays(10)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint().'?date_from='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateTo(): void
    {
        Settlement::factory()->count(2)->for($this->merchant)->create(['period_start' => now()->subDays(10)]);
        Settlement::factory()->count(3)->for($this->merchant)->create(['period_start' => now()->subDays(3)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint().'?date_to='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        Settlement::factory()->count(10)->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listSettlementsEndpoint().'?limit=4');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(4, 'data')
            ->assertJson(['meta' => ['perPage' => 4, 'hasMore' => true]]);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listSettlementsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::listSettlementsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
