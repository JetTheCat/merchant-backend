<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantTransactionsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private const array VALID_TRANSACTION_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'parentTransactionId',
                'parentTransactionReference',
                'merchantUser',
                'type',
                'typeLabel',
                'status',
                'statusLabel',
                'referenceCode',
                'amount',
                'paidAmount',
                'platformFeeRate',
                'platformFee',
                'processingFee',
                'settlementAmount',
                'balanceBefore',
                'balanceAfter',
                'notes',
                'metadata',
                'createdAt',
            ],
        ],
        'meta' => [
            'perPage',
            'nextCursor',
            'prevCursor',
            'hasMore',
        ],
    ];

    protected function setup(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->for($this->merchant)->create();

        Transaction::query()->withTrashed()->forceDelete();
    }

    public function testMerchantUserCanListTransactions(): void
    {
        CreditTransaction::factory()->count(3)->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_TRANSACTION_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testOnlyOwnMerchantTransactionsAreReturned(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create();
        CreditTransaction::factory()->count(3)->for(Merchant::factory()->active()->create())->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testTransactionsAreReturnedLatestFirst(): void
    {
        $first = CreditTransaction::factory()->for($this->merchant)->create(['created_at' => now()->subMinutes(10)]);
        $second = CreditTransaction::factory()->for($this->merchant)->create(['created_at' => now()->subMinutes(5)]);
        $third = CreditTransaction::factory()->for($this->merchant)->create(['created_at' => now()]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertEquals([(string) $third->id, (string) $second->id, (string) $first->id], $ids->all());
    }

    public function testTransactionResponseIncludesCustomerDetails(): void
    {
        $user = User::factory()->create();

        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($user)
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint())
            ->assertSuccessful();

        $response->assertJsonPath('data.0.customer.id', $user->id)
            ->assertJsonPath('data.0.customer.firstName', $user->first_name)
            ->assertJsonPath('data.0.customer.lastName', $user->last_name);
    }

    public function testItCanFilterByType(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create([
            'type' => TransactionType::CREDIT_REDEMPTION,
        ]);

        CreditTransaction::factory()->count(3)->for($this->merchant)->create([
            'type' => TransactionType::CREDIT_ADJUSTMENT,
        ]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?type=credit_redemption');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByMerchantUser(): void
    {
        $otherMerchantUser = MerchantUser::factory()->for($this->merchant)->create();

        CreditTransaction::factory()->count(2)->for($this->merchant)->for($this->merchantUser, 'merchantUser')->create();
        CreditTransaction::factory()->count(3)->for($this->merchant)->for($otherMerchantUser, 'merchantUser')->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?merchant_user_id='.$this->merchantUser->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testFilteringByMerchantUserFromAnotherMerchantReturnsValidationError(): void
    {
        $anotherMerchant = Merchant::factory()->active()->create();
        $foreignMerchantUser = MerchantUser::factory()->for($anotherMerchant)->create();

        CreditTransaction::factory()
            ->for($foreignMerchantUser, 'merchantUser')
            ->for($anotherMerchant)
            ->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?merchant_user_id='.$foreignMerchantUser->id);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantUserId']);
    }

    public function testItCanFilterByStatus(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create(['status' => TransactionStatus::COMPLETED]);
        CreditTransaction::factory()->count(3)->for($this->merchant)->create(['status' => TransactionStatus::PENDING]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?status=completed');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByReference(): void
    {
        $match = CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-MATCH-001']);
        CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-OTHER-001']);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?reference=TXN-MATCH');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.referenceCode', $match->reference_code);
    }

    #[DataProvider('providesPartialReferenceMatches')]
    public function testItCanFilterByPartialReference(string $reference): void
    {
        $match = CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-MATCH-001']);
        CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-OTHER-001']);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?reference='.$reference);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.referenceCode', $match->reference_code);
    }

    public static function providesPartialReferenceMatches(): array
    {
        return [
            'prefix' => ['reference' => 'TXN-MATCH'],
            'middle' => ['reference' => 'MATCH'],
            'suffix' => ['reference' => 'MATCH-001'],
        ];
    }

    public function testItCanFilterByReferenceCaseInsensitively(): void
    {
        $match = CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-MATCH-001']);
        CreditTransaction::factory()->for($this->merchant)->create(['reference_code' => 'TXN-OTHER-001']);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?reference=txn-match');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.referenceCode', $match->reference_code);
    }

    public function testDateFiltersConvertTimezoneToUtc(): void
    {
        $timezone = 'Asia/Kuala_Lumpur';
        $windowStart = Carbon::parse('2026-05-08 00:00:00', $timezone)->utc();
        $windowEnd = Carbon::parse('2026-05-08 23:59:59', $timezone)->utc();

        CreditTransaction::factory()->for($this->merchant)->count(4)->sequence(
            ['created_at' => $windowStart->clone()->subSecond()],
            ['created_at' => $windowStart],
            ['created_at' => $windowEnd],
            ['created_at' => $windowEnd->clone()->addSecond()],
        )->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?'.http_build_query([
                'date_from' => '2026-05-08 00:00:00',
                'date_to' => '2026-05-08 23:59:59',
                'timezone' => $timezone,
            ]));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateFrom(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create(['created_at' => now()->subDays(3)]);
        CreditTransaction::factory()->count(3)->for($this->merchant)->create(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?date_from='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateTo(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create(['created_at' => now()->subDays(10)]);
        CreditTransaction::factory()->count(3)->for($this->merchant)->create(['created_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?date_to='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateRange(): void
    {
        CreditTransaction::factory()->count(2)->for($this->merchant)->create(['created_at' => now()->subDays(5)]);
        CreditTransaction::factory()->count(3)->for($this->merchant)->create(['created_at' => now()->subDays(15)]);
        CreditTransaction::factory()->count(4)->for($this->merchant)->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?date_from='.now()->subDays(10)->toDateString().'&date_to='.now()->subDays(3)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByMerchantLocation(): void
    {
        [$location, $otherLocation] = MerchantLocation::factory()
            ->for($this->merchant)
            ->count(2)
            ->create();

        CreditTransaction::factory()
            ->count(2)
            ->for($this->merchant)
            ->for($this->merchantUser)
            ->for($location)
            ->create();

        CreditTransaction::factory()
            ->count(3)
            ->for($this->merchant)
            ->for($this->merchantUser)
            ->for($otherLocation)
            ->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?merchant_location_id='.$location->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testFilteringByMerchantLocationFromAnotherMerchantReturnsValidationError(): void
    {
        $otherMerchantLocation = MerchantLocation::factory()
            ->for(Merchant::factory()->active()->create())
            ->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?merchant_location_id='.$otherMerchantLocation->id);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['merchantLocationId']);
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        CreditTransaction::factory()->count(10)->for($this->merchant)->create();

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?limit=4');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(4, 'data')
            ->assertJson(['meta' => ['perPage' => 4, 'hasMore' => true]]);
    }

    public function testPaginationCursorNavigatesToNextPage(): void
    {
        CreditTransaction::factory()->count(5)->for($this->merchant)->create();

        $firstPage = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?limit=3');

        $nextCursor = $firstPage->json('meta.nextCursor');

        $secondPage = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?'.http_build_query(['limit' => 3, 'cursor' => $nextCursor]));

        $secondPage->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJson(['meta' => ['hasMore' => false]]);
    }

    public function testReturnsEmptyListWhenNoTransactions(): void
    {
        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
    }

    public function testRedemptionTransactionResponseIncludesSettlementAmount(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->for($this->merchant)
            ->create(['settlement_amount' => 85.00]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint())
            ->assertSuccessful();

        $response->assertJsonPath('data.0.settlementAmount', '85.00');
    }

    public function testRefundTransactionResponseIncludesParentTransactionData(): void
    {
        $redemption = CreditTransaction::factory()
            ->redemption()
            ->for($this->merchant)
            ->create();

        CreditTransaction::factory()
            ->refund()
            ->for($this->merchant)
            ->create(['parent_transaction_id' => $redemption->id]);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint())
            ->assertSuccessful();

        $refund = collect($response->json('data'))
            ->firstWhere('type', 'credit_refund');

        $this->assertEquals($redemption->id, $refund['parentTransactionId']);
        $this->assertEquals($redemption->reference_code, $refund['parentTransactionReference']);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testRegularUserCannotAccessThisEndpoint(): void
    {
        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson(self::listMerchantTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
