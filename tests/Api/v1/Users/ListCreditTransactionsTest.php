<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class ListCreditTransactionsTest extends TestCase
{
    use UserEndpoints;

    private User $user;

    private const VALID_TRANSACTION_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'merchant',
                'type',
                'typeLabel',
                'status',
                'statusLabel',
                'referenceCode',
                'amount',
                'paidAmount',
                'balanceBefore',
                'balanceAfter',
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function testUserCanListTheirCreditTransactions(): void
    {
        CreditTransaction::factory()->count(3)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'status' => TransactionStatus::COMPLETED,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_TRANSACTION_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testTransactionsAreReturnedLatestFirst(): void
    {
        $first = CreditTransaction::factory()->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'created_at' => now()->subMinutes(10),
        ]);

        $second = CreditTransaction::factory()->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'created_at' => now()->subMinutes(5),
        ]);

        $third = CreditTransaction::factory()->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertEquals([(string) $third->id, (string) $second->id, (string) $first->id], $ids->all());
    }

    public function testOnlyOwnTransactionsAreReturned(): void
    {
        CreditTransaction::factory()->count(2)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        CreditTransaction::factory()->count(3)->for(User::factory()->create())->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByMerchant(): void
    {
        $merchant = Merchant::factory()->create();
        $otherMerchant = Merchant::factory()->create();

        CreditTransaction::factory()->count(2)->for($this->user)->for($merchant)->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        CreditTransaction::factory()->count(3)->for($this->user)->for($otherMerchant)->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?merchant_id='.$merchant->id);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByType(): void
    {
        CreditTransaction::factory()->count(2)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'type' => TransactionType::CREDIT_PURCHASE,
        ]);

        CreditTransaction::factory()->count(3)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
            'type' => TransactionType::CREDIT_REDEMPTION,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?type=credit_purchase');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateFrom(): void
    {
        CreditTransaction::factory()->count(2)->for($this->user)->create(['created_at' => now()->subDays(3)]);
        CreditTransaction::factory()->count(3)->for($this->user)->create(['created_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?date_from='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateTo(): void
    {
        CreditTransaction::factory()->count(2)->for($this->user)->create(['created_at' => now()->subDays(10)]);
        CreditTransaction::factory()->count(3)->for($this->user)->create(['created_at' => now()->subDays(3)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?date_to='.now()->subDays(5)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testItCanFilterByDateRange(): void
    {
        CreditTransaction::factory()->count(2)->for($this->user)->create(['created_at' => now()->subDays(5)]);
        CreditTransaction::factory()->count(3)->for($this->user)->create(['created_at' => now()->subDays(15)]);
        CreditTransaction::factory()->count(4)->for($this->user)->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?date_from='.now()->subDays(10)->toDateString().'&date_to='.now()->subDays(3)->toDateString());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        CreditTransaction::factory()->count(10)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?limit=4');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(4, 'data')
            ->assertJson(['meta' => ['perPage' => 4, 'hasMore' => true]]);
    }

    public function testPaginationCursorNavigatesToNextPage(): void
    {
        CreditTransaction::factory()->count(5)->for($this->user)->create([
            'category' => TransactionCategory::CREDIT,
        ]);

        $firstPage = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?limit=3');

        $nextCursor = $firstPage->json('meta.nextCursor');

        $secondPage = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint().'?'.http_build_query(['limit' => 3, 'cursor' => $nextCursor]));

        $secondPage->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJson(['meta' => ['hasMore' => false]]);
    }

    public function testReturnsEmptyListWhenNoTransactions(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessThisEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()
            ->for(Merchant::factory()->create())
            ->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->getJson(self::listCreditTransactionsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
