<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\Category;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\MerchantPortalEndpoints;
use Tests\TestCase;

class ListUserCreditsTest extends TestCase
{
    use MerchantPortalEndpoints;

    private User $user;

    private const VALID_USER_CREDITS_STRUCTURE = [
        'data' => [
            '*' => [
                'id',
                'merchantId',
                'merchant',
                'balance',
                'totalPurchased',
                'totalRedeemed',
                'lastPurchaseAt',
                'lastRedemptionAt',
                'createdAt',
                'updatedAt',
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

    public function testUserCanListTheirCreditWallets(): void
    {
        UserCredit::factory()->count(3)->for($this->user)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_USER_CREDITS_STRUCTURE)
            ->assertJsonCount(3, 'data');
    }

    public function testWalletsWithZeroBalanceAreExcluded(): void
    {
        UserCredit::factory()->count(2)->for($this->user)->withBalance(50)->create();
        UserCredit::factory()->for($this->user)->create(['balance' => 0]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testOnlyOwnCreditsAreReturned(): void
    {
        UserCredit::factory()->count(2)->for($this->user)->withBalance(100)->create();
        UserCredit::factory()->count(3)->for(User::factory()->create())->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');
    }

    public function testReturnsEmptyListWhenNoCredits(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
    }

    public function testItCanSearchByMerchantName(): void
    {
        $match = Merchant::factory()->create(['name' => 'Sushi Tei Restaurant']);
        $noMatch = Merchant::factory()->create(['name' => 'Pizza Express']);

        UserCredit::factory()->for($this->user)->for($match)->withBalance(100)->create();
        UserCredit::factory()->for($this->user)->for($noMatch)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?search=Sushi');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['merchantId' => $match->id]]]);
    }

    public function testItCanSearchByMerchantBusinessName(): void
    {
        $match = Merchant::factory()->create(['business_name' => 'Sushi Tei Sdn Bhd']);
        $noMatch = Merchant::factory()->create(['business_name' => 'Pizza Corp Sdn Bhd']);

        UserCredit::factory()->for($this->user)->for($match)->withBalance(100)->create();
        UserCredit::factory()->for($this->user)->for($noMatch)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?search=Sushi+Tei');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['merchantId' => $match->id]]]);
    }

    public function testItCanSearchByMerchantDescription(): void
    {
        $match = Merchant::factory()->create(['description' => 'Authentic Japanese cuisine']);
        $noMatch = Merchant::factory()->create(['description' => 'Best Italian pizzas']);

        UserCredit::factory()->for($this->user)->for($match)->withBalance(100)->create();
        UserCredit::factory()->for($this->user)->for($noMatch)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?search=Japanese');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['merchantId' => $match->id]]]);
    }

    public function testItCanFilterByCategory(): void
    {
        $category = Category::factory()->create(['slug' => 'japanese']);
        $otherCategory = Category::factory()->create(['slug' => 'italian']);

        $matchMerchant = Merchant::factory()->for($category)->create();
        $otherMerchant = Merchant::factory()->for($otherCategory)->create();

        UserCredit::factory()->for($this->user)->for($matchMerchant)->withBalance(100)->create();
        UserCredit::factory()->for($this->user)->for($otherMerchant)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?category=japanese');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['merchantId' => $matchMerchant->id]]]);
    }

    public function testPaginationRespectsLimitParameter(): void
    {
        UserCredit::factory()->count(10)->for($this->user)->withBalance(100)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?limit=3');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data')
            ->assertJson(['meta' => ['perPage' => 3, 'hasMore' => true]])
            ->assertJsonStructure(['meta' => ['nextCursor']]);
    }

    public function testPaginationCursorNavigatesToNextPage(): void
    {
        UserCredit::factory()->count(5)->for($this->user)->withBalance(100)->create();

        $firstPage = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?limit=3');

        $nextCursor = $firstPage->json('meta.nextCursor');

        $secondPage = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::listUserCreditsEndpoint().'?'.http_build_query(['limit' => 3, 'cursor' => $nextCursor]));

        $secondPage->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJson(['meta' => ['hasMore' => false]]);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessThisEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()
            ->for(Merchant::factory()->create())
            ->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->getJson(self::listUserCreditsEndpoint());

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
