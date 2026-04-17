<?php

declare(strict_types=1);

namespace Tests\Api\v1\Users;

use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use Symfony\Component\HttpFoundation\Response;
use Tests\Api\v1\Traits\UserEndpoints;
use Tests\TestCase;

class ConfirmRedemptionTest extends TestCase
{
    use UserEndpoints;

    private User $user;

    private Merchant $merchant;

    private UserCredit $userCredit;

    private CreditTransaction $pendingTransaction;

    private const string VALID_TOKEN = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

    private const array VALID_STRUCTURE = [
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
        $this->merchant = Merchant::factory()->active()->create();

        $this->userCredit = UserCredit::factory()
            ->withBalance(100.00)
            ->for($this->user)
            ->for($this->merchant)
            ->create();

        $this->pendingTransaction = CreditTransaction::factory()
            ->redemption()
            ->pending()
            ->for($this->merchant)
            ->create([
                'amount' => 25.00,
                'qr_code' => self::VALID_TOKEN,
                'qr_expires_at' => now()->addMinutes(5),
            ]);
    }

    public function testUserCanConfirmRedemption(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure(self::VALID_STRUCTURE)
            ->assertJsonPath('data.status', 'completed');
    }

    public function testBalanceIsDeductedAfterRedemption(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $this->assertDatabaseHas('user_credits', [
            'id' => $this->userCredit->id,
            'merchant_id' => $this->merchant->id,
            'balance' => '75.00',
        ]);
    }

    public function testTransactionIsMarkedAsCompleted(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $this->assertDatabaseHas('transactions', [
            'id' => $this->pendingTransaction->id,
            'status' => TransactionStatus::COMPLETED,
            'user_id' => $this->user->id,
        ]);
    }

    public function testInvalidTokenReturnsValidationError(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => 'invalid-token']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testAlreadyRedeemedTokenReturnsValidationError(): void
    {
        $this->pendingTransaction->update(['status' => TransactionStatus::COMPLETED]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testCancelledTokenReturnsValidationError(): void
    {
        $this->pendingTransaction->update(['status' => TransactionStatus::CANCELLED]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testExpiredTokenReturnsValidationError(): void
    {
        $this->pendingTransaction->update(['qr_expires_at' => now()->subMinute()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testNoWalletForMerchantReturnsValidationError(): void
    {
        $this->userCredit->delete();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testInsufficientBalanceReturnsValidationError(): void
    {
        $this->userCredit->update(['balance' => 10.00]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testTokenIsRequired(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson(self::confirmRedemptionEndpoint(), []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['token']);
    }

    public function testRequiresAuthentication(): void
    {
        $response = $this->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testMerchantUserCannotAccessThisEndpoint(): void
    {
        $merchantUser = MerchantUser::factory()->owner()->active()
            ->for($this->merchant)
            ->create();

        $response = $this->actingAs($merchantUser, 'merchant_user')
            ->postJson(self::confirmRedemptionEndpoint(), ['token' => self::VALID_TOKEN]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }
}
