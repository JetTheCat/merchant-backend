<?php

declare(strict_types=1);

namespace Tests\Unit\Services\RedemptionService;

use App\Enums\ClawbackStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\Settlement;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\RedemptionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RefundRedemptionTest extends TestCase
{
    private RedemptionService $service;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RedemptionService::class);

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->owner()->for($this->merchant)->create();
        $this->customer = User::factory()->create();
    }

    public function testRefundRedemptionCreatesRefundTransactionAndRestoresBalance(): void
    {
        $wallet = UserCredit::factory()->for($this->customer)->for($this->merchant)->create([
            'balance' => 60,
            'cash_balance' => 51,
            'gross_paid_balance' => 57,
            'total_redeemed' => 40,
        ]);

        $redemption = CreditTransaction::factory()->redemption()->completed()
            ->for($this->merchant)
            ->for($this->customer)
            ->create([
                'user_credit_id' => $wallet->id,
                'amount' => 40,
                'settlement_amount' => 34,
                'gross_settlement_amount' => 38,
                'balance_before' => 100,
                'balance_after' => 60,
            ]);

        $refund = $this->service->refundRedemption($redemption, $this->merchantUser, 'Wrong amount.');

        $this->assertEquals(TransactionType::CREDIT_REFUND, $refund->type);
        $this->assertEquals(TransactionStatus::COMPLETED, $refund->status);
        $this->assertEquals('40.00', $refund->amount);
        $this->assertEquals('60.00', $refund->balance_before);
        $this->assertEquals('100.00', $refund->balance_after);
        $this->assertEquals('Wrong amount.', $refund->notes);

        $this->assertEquals(TransactionStatus::REVERSED, $redemption->fresh()->status);
        $this->assertEquals($redemption->id, $refund->parent_transaction_id);

        $wallet->refresh();
        $this->assertEquals('100.00', $wallet->balance);

        // cash_balance and gross_paid_balance are restored using the settlement_amount and
        // gross_settlement_amount frozen on the original redemption row at the time of redemption
        $this->assertEquals('85.00', $wallet->cash_balance);
        $this->assertEquals('95.00', $wallet->gross_paid_balance);
        $this->assertEquals('0.00', $wallet->total_redeemed);
    }

    public function testRefundRedemptionDoesNotCreateClawbackWhenNoSettlement(): void
    {
        $wallet = UserCredit::factory()->for($this->customer)->for($this->merchant)->create([
            'balance' => 60,
            'cash_balance' => 51,
            'gross_paid_balance' => 57,
            'total_redeemed' => 40,
        ]);

        $redemption = CreditTransaction::factory()->redemption()->completed()
            ->for($this->merchant)
            ->for($this->customer)
            ->create([
                'user_credit_id' => $wallet->id,
                'amount' => 40,
                'settlement_amount' => 34,
                'gross_settlement_amount' => 38,
                'balance_before' => 100,
                'balance_after' => 60,
            ]);

        $this->service->refundRedemption($redemption, $this->merchantUser);

        $this->assertDatabaseMissing('settlement_clawbacks', [
            'original_redemption_id' => $redemption->id,
        ]);
    }

    public function testRefundRedemptionCreatesClawbackWhenRedemptionIsAssignedToASettlement(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $wallet = UserCredit::factory()->for($this->customer)->for($this->merchant)->create([
            'balance' => 60,
            'cash_balance' => 51,
            'gross_paid_balance' => 57,
            'total_redeemed' => 40,
        ]);

        $redemption = CreditTransaction::factory()->redemption()->completed()
            ->for($this->merchant)
            ->for($this->customer)
            ->create([
                'user_credit_id' => $wallet->id,
                'settlement_id' => $settlement->id,
                'amount' => 40,
                'settlement_amount' => 34,
                'gross_settlement_amount' => 38,
                'balance_before' => 100,
                'balance_after' => 60,
            ]);

        $refund = $this->service->refundRedemption($redemption, $this->merchantUser);

        $this->assertEquals($redemption->id, $refund->parent_transaction_id);

        $this->assertDatabaseHas('settlement_clawbacks', [
            'merchant_id' => $this->merchant->id,
            'original_redemption_id' => $redemption->id,
            'refund_transaction_id' => $refund->id,
            'amount' => '34.00',
            'status' => ClawbackStatus::PENDING,
            'settlement_id' => null,
        ]);
    }

    public function testRefundRedemptionThrowsIfNotCreditRedemptionType(): void
    {
        $this->expectException(ValidationException::class);

        $purchase = CreditTransaction::factory()->purchase()->for($this->merchant)->create();

        $this->service->refundRedemption($purchase, $this->merchantUser);
    }

    public function testRefundRedemptionThrowsIfNotCompleted(): void
    {
        $this->expectException(ValidationException::class);

        $redemption = CreditTransaction::factory()->redemption()
            ->for($this->merchant)
            ->create(['status' => TransactionStatus::REVERSED]);

        $this->service->refundRedemption($redemption, $this->merchantUser);
    }

    public function testRefundRedemptionThrowsIfUserCreditIdIsNull(): void
    {
        $this->expectException(ValidationException::class);

        $redemption = CreditTransaction::factory()->redemption()->completed()
            ->for($this->merchant)
            ->create(['user_credit_id' => null]);

        $this->service->refundRedemption($redemption, $this->merchantUser);
    }
}
