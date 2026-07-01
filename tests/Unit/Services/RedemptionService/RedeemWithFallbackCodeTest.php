<?php

declare(strict_types=1);

namespace Tests\Unit\Services\RedemptionService;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\RedemptionCompleted;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\RedemptionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RedeemWithFallbackCodeTest extends TestCase
{
    private RedemptionService $service;

    private User $user;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private MerchantLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([RedemptionCompleted::class]);

        $this->service = app(RedemptionService::class);

        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->for($this->merchant)->create();
        $this->location = MerchantLocation::factory()->active()->for($this->merchant)->create();
    }

    private function pendingCode(float $amount, bool $withLocation = true): CreditTransaction
    {
        return CreditTransaction::factory()
            ->redemption()
            ->pending()
            ->for($this->merchant)
            ->for($this->merchantUser)
            ->create([
                'merchant_location_id' => $withLocation ? $this->location->id : null,
                'amount' => $amount,
                'fallback_code' => fake()->regexify('[A-Z0-9]{6}'),
                'expires_at' => now()->addMinutes(5),
            ]);
    }

    public function testRedeemWithFallbackCodeCompletesTransactionAndDecrementsBalance(): void
    {
        $pending = $this->pendingCode(40);

        $wallet = UserCredit::factory()->for($this->user)->for($this->merchant)->withBalance(100)->create();

        $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        $this->assertDatabaseHas('transactions', [
            'id' => $pending->id,
            'user_id' => $this->user->id,
            'user_credit_id' => $wallet->id,
            'category' => TransactionCategory::CREDIT,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::COMPLETED,
            'amount' => '40.00',
            'balance_before' => '100.00',
            'balance_after' => '60.00',
        ]);

        $wallet->refresh();

        $this->assertEquals('60.00', $wallet->balance);
        $this->assertEquals('60.00', $wallet->cash_balance);
        $this->assertEquals('40.00', $wallet->total_redeemed);
    }

    public function testSettlementAmountUsesNetCashRatio(): void
    {
        $pending = $this->pendingCode(40);

        $userCredit = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 100,
            'cash_balance' => 85,
            'gross_paid_balance' => 95,
        ]);

        $transaction = $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        // netRatio = 85 / 100 = 0.85000; settlementAmount = 40 * 0.85 = 34.00
        $this->assertEquals('34.00', $transaction->settlement_amount);
        $this->assertEquals('51.00', $userCredit->fresh()->cash_balance);
    }

    public function testGrossSettlementAmountUsesGrossPaidRatioIndependently(): void
    {
        $pending = $this->pendingCode(60);

        $userCredit = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 160,
            'cash_balance' => 136,
            'gross_paid_balance' => 150,
        ]);

        $transaction = $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        // netRatio = 136 / 160 = 0.85000; settlementAmount = 60 * 0.85 = 51.00
        // grossRatio = 150 / 160 = 0.93750; grossSettlementAmount = 60 * 0.9375 = 56.25
        $this->assertEquals('51.00', $transaction->settlement_amount);
        $this->assertEquals('56.25', $transaction->gross_settlement_amount);

        $userCredit->refresh();
        $this->assertEquals('85.00', $userCredit->cash_balance);
        $this->assertEquals('93.75', $userCredit->gross_paid_balance);
    }

    public function testFullBalanceRedemptionDrainsWalletToZero(): void
    {
        $pending = $this->pendingCode(50);

        $wallet = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 50,
            'cash_balance' => 42,
            'gross_paid_balance' => 48,
        ]);

        $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        $wallet->refresh();

        // netRatio = 42 / 50 = 0.84000; settlementAmount = 50 * 0.84 = 42.00
        // grossRatio = 48 / 50 = 0.96000; grossSettlementAmount = 50 * 0.96 = 48.00
        $this->assertEquals('0.00', $wallet->balance);
        $this->assertEquals('0.00', $wallet->cash_balance);
        $this->assertEquals('0.00', $wallet->gross_paid_balance);
        $this->assertEquals('50.00', $wallet->total_redeemed);
    }

    public function testSettlementAmountIsZeroWhenCashBalanceIsZero(): void
    {
        $pending = $this->pendingCode(20);

        // purely promotional credit the customer never actually paid for
        $userCredit = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 50,
            'cash_balance' => 0,
            'gross_paid_balance' => 0,
        ]);

        $transaction = $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        $this->assertEquals('0.00', $transaction->settlement_amount);
        $this->assertEquals('0.00', $transaction->gross_settlement_amount);
        $this->assertEquals('0.00', $userCredit->fresh()->cash_balance);
    }

    public function testRedemptionCompletedEventIsDispatchedWhenLocationIsSet(): void
    {
        $pending = $this->pendingCode(40, withLocation: true);

        UserCredit::factory()->for($this->user)->for($this->merchant)->withBalance(100)->create();

        $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        Event::assertDispatched(RedemptionCompleted::class);
    }

    public function testRedemptionCompletedEventIsNotDispatchedWhenLocationIsNull(): void
    {
        $pending = $this->pendingCode(40, withLocation: false);

        UserCredit::factory()->for($this->user)->for($this->merchant)->withBalance(100)->create();

        $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);

        Event::assertNotDispatched(RedemptionCompleted::class);
    }

    public function testInvalidCodeThrowsValidationException(): void
    {
        try {
            $this->service->redeemWithFallbackCode('XXXXXX', $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testExpiredCodeThrowsValidationException(): void
    {
        $pending = CreditTransaction::factory()
            ->redemption()
            ->pending()
            ->for($this->merchant)
            ->for($this->merchantUser)
            ->create([
                'fallback_code' => fake()->regexify('[A-Z0-9]{6}'),
                'expires_at' => now()->subMinute(),
            ]);

        try {
            $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testNoWalletThrowsValidationException(): void
    {
        $pending = $this->pendingCode(40);

        try {
            $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testInsufficientBalanceThrowsValidationException(): void
    {
        $pending = $this->pendingCode(25);

        UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 10,
            'cash_balance' => 10,
            'gross_paid_balance' => 10,
        ]);

        try {
            $this->service->redeemWithFallbackCode($pending->fallback_code, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }
}
