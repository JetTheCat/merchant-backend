<?php

declare(strict_types=1);

namespace Tests\Unit\Services\RedemptionService;

use App\Enums\TransactionCategory;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\RedemptionCompleted;
use App\Models\Merchant;
use App\Models\MerchantLocation;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\RedemptionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RedeemWithStaticQrTest extends TestCase
{
    private RedemptionService $service;

    private User $user;

    private Merchant $merchant;

    private MerchantLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([RedemptionCompleted::class]);

        $this->service = app(RedemptionService::class);

        $this->user = User::factory()->create();
        $this->merchant = Merchant::factory()->active()->create();
        $this->location = MerchantLocation::factory()->active()->for($this->merchant)->create();
    }

    public function testRedeemWithStaticQrCreatesCompletedTransactionAndDecrementsBalance(): void
    {
        $wallet = UserCredit::factory()
            ->for($this->user)
            ->for($this->merchant)
            ->withBalance(100)
            ->create();

        $this->service->redeemWithStaticQr($this->location->redemption_code, 40, $this->user);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $wallet->user_id,
            'merchant_id' => $wallet->merchant_id,
            'merchant_location_id' => $this->location->id,
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
        $userCredit = UserCredit::factory()
            ->for($this->user)
            ->for($this->merchant)
            ->create([
                'balance' => 100,
                'cash_balance' => 85,
                'gross_paid_balance' => 95,
            ]);

        $transaction = $this->service->redeemWithStaticQr($this->location->redemption_code, 40, $this->user);

        // netRatio = 85 / 100 = 0.85000; settlementAmount = 40 * 0.85 = 34.00
        $this->assertEquals('34.00', $transaction->settlement_amount);
        $this->assertEquals('51.00', $userCredit->fresh()->cash_balance);
    }

    public function testGrossSettlementAmountUsesGrossPaidRatioIndependently(): void
    {
        $userCredit = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 160,
            'cash_balance' => 136,
            'gross_paid_balance' => 150,
        ]);

        $transaction = $this->service->redeemWithStaticQr($this->location->redemption_code, 60, $this->user);

        // netRatio = 136 / 160 = 0.85000; settlementAmount = 60 * 0.85 = 51.00
        // grossRatio = 150 / 160 = 0.93750; grossSettlementAmount = 60 * 0.9375 = 56.25
        $this->assertEquals('51.00', $transaction->settlement_amount);
        $this->assertEquals('56.25', $transaction->gross_settlement_amount);
        $this->assertNotEquals($transaction->settlement_amount, $transaction->gross_settlement_amount);

        $userCredit->refresh();
        $this->assertEquals('85.00', $userCredit->cash_balance);
        $this->assertEquals('93.75', $userCredit->gross_paid_balance);
    }

    public function testFullBalanceRedemptionDrainsWalletToZero(): void
    {
        $wallet = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 50,
            'cash_balance' => 42,
            'gross_paid_balance' => 48,
        ]);

        $this->service->redeemWithStaticQr($this->location->redemption_code, 50, $this->user);

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
        // purely promotional credit the customer never actually paid for
        $userCredit = UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 50,
            'cash_balance' => 0,
            'gross_paid_balance' => 0,
        ]);

        $transaction = $this->service->redeemWithStaticQr($this->location->redemption_code, 20, $this->user);

        $this->assertEquals('0.00', $transaction->settlement_amount);
        $this->assertEquals('0.00', $transaction->gross_settlement_amount);
        $this->assertEquals('0.00', $userCredit->fresh()->cash_balance);
    }

    public function testInvalidQrCodeThrowsValidationException(): void
    {
        try {
            $this->service->redeemWithStaticQr(Str::uuid()->toString(), 10, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testInactiveLocationThrowsValidationException(): void
    {
        $inactiveLocation = MerchantLocation::factory()->inactive()->for($this->merchant)->create();

        try {
            $this->service->redeemWithStaticQr($inactiveLocation->redemption_code, 10, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testNoWalletThrowsValidationException(): void
    {
        try {
            $this->service->redeemWithStaticQr($this->location->redemption_code, 10, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('code', $e->errors());
        }
    }

    public function testInsufficientBalanceThrowsValidationException(): void
    {
        UserCredit::factory()->for($this->user)->for($this->merchant)->create([
            'balance' => 10,
            'cash_balance' => 10,
            'gross_paid_balance' => 10,
        ]);

        try {
            $this->service->redeemWithStaticQr($this->location->redemption_code, 25, $this->user);
            $this->fail('Expected a ValidationException to be thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }
    }

    public function testRedemptionCompletedEventIsDispatched(): void
    {
        UserCredit::factory()->for($this->user)->for($this->merchant)->withBalance(100)->create();

        $this->service->redeemWithStaticQr($this->location->redemption_code, 40, $this->user);

        Event::assertDispatched(RedemptionCompleted::class);
    }
}
