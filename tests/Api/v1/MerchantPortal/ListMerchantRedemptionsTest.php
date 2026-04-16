<?php

declare(strict_types=1);

namespace Tests\Api\v1\MerchantPortal;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantRedemptionsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;
    private MerchantUser $merchantUser;

    private const VALID_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'merchantUser',
                'type',
                'typeLabel',
                'status',
                'statusLabel',
                'referenceCode',
                'amount',
                'paidAmount',
                'balanceBefore',
                'balanceAfter',
                'notes',
                'metadata',
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

    public function testMerchantUserCanListRedemptions(): void
    {
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testOnlyRedemptionsAreReturned(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create();
        CreditTransaction::factory()->count(3)->purchase()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testOnlyOwnMerchantRedemptionsAreReturned(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create();
        CreditTransaction::factory()->count(3)->redemption()->for(Merchant::factory()->active()->create())->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByMerchantUser(): void
    {
        $otherMerchantUser = MerchantUser::factory()->for($this->merchant)->create();

        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->for($this->merchantUser, 'merchantUser')->create();
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->for($otherMerchantUser, 'merchantUser')->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?merchant_user_id='.$this->merchantUser->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByStatus(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create(['status' => TransactionStatus::COMPLETED]);
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create(['status' => TransactionStatus::PENDING]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?status=completed');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateFrom(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(3)]);
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?date_from='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateTo(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(10)]);
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?date_to='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateRange(): void
    {
        CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(5)]);
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create(['created_at' => now()->subDays(15)]);
        CreditTransaction::factory()->count(4)->redemption()->for($this->merchant)->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?date_from='.now()->subDays(10)->toDateString().'&date_to='.now()->subDays(3)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        CreditTransaction::factory()->count(10)->redemption()->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantRedemptionsEndpoint().'?limit=4');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(4, 'data')
            ->assertJson(['meta' => ['perPage' => 4, 'hasMore' => true]]);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantRedemptionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::listMerchantRedemptionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
