<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class GetCreditTransactionTest extends TestCase
{
    use UserEndpoints;

    private User $user;

    private const VALID_TRANSACTION_STRUCTURE = [
        'data' => [
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
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function testUserCanGetTheirTransaction(): void
    {
        $transaction = CreditTransaction::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getCreditTransactionEndpoint($transaction->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_TRANSACTION_STRUCTURE);
    }

    public function testMerchantIsLoadedInResponse(): void
    {
        $merchant = Merchant::factory()->create();
        $transaction = CreditTransaction::factory()->for($this->user)->for($merchant)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getCreditTransactionEndpoint($transaction->id));

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.merchant.id', $merchant->id);
    }

    public function testUserCannotGetAnotherUsersTransaction(): void
    {
        $otherUser = User::factory()->create();
        $transaction = CreditTransaction::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getCreditTransactionEndpoint($transaction->id));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsNotFoundForNonExistentTransaction(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson(self::getCreditTransactionEndpoint('00000000-0000-0000-0000-000000000000'));

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function testRequiresAuthentication(): void
    {
        $transaction = CreditTransaction::factory()->for($this->user)->create();

        $response = $this->getJson(self::getCreditTransactionEndpoint($transaction->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessThisEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()
            ->for(Merchant::factory()->create())
            ->create();

        $transaction = CreditTransaction::factory()->for($this->user)->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->getJson(self::getCreditTransactionEndpoint($transaction->id));

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
