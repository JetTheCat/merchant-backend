<?php

declare(strict_types=1);

namespace Tests\Api\v1\Merchants;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Transaction;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListMerchantTransactionsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private const VALID_TRANSACTION_STRUCTURE = [
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
        CreditTransaction::factory()->count(2)->for($this->merchant)->create(['reference_code' => 'TXN-MATCH-001']);
        CreditTransaction::factory()->count(3)->for($this->merchant)->create(['reference_code' => 'TXN-OTHER-001']);

        $response = $this->actingAs($this->merchantUser, self::MERCHANT_USER_GUARD)
            ->getJson(self::listMerchantTransactionsEndpoint().'?reference=TXN-MATCH');

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
