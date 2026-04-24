<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\RedemptionService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RedemptionServiceTest extends TestCase
{
    private RedemptionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RedemptionService::class);
    }

    public function testGenerateQrCreatesPendingTransaction(): void
    {
        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();

        $transaction = $this->service->generateQr($merchantUser, 50.00);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'merchant_id' => $merchant->id,
            'merchant_user_id' => $merchantUser->id,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::PENDING,
            'amount' => '50.00',
        ]);

        $this->assertNotNull($transaction->qr_code);
        $this->assertNotNull($transaction->redemption_code);
        $this->assertNotNull($transaction->qr_expires_at);
        $this->assertTrue($transaction->qr_expires_at->isFuture());
    }

    public function testGenerateQrCancelsPreviousPendingQrForSameMerchantUser(): void
    {
        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();

        $existing = CreditTransaction::factory()
            ->redemption()
            ->for($merchant)
            ->for($merchantUser, 'merchantUser')
            ->create(['status' => TransactionStatus::PENDING]);

        $this->service->generateQr($merchantUser, 30.00);

        $this->assertEquals(TransactionStatus::CANCELLED, $existing->fresh()->status);
    }

    public function testGenerateQrDoesNotCancelPendingQrsForOtherMerchantUsers(): void
    {
        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();
        $otherMerchantUser = MerchantUser::factory()->for($merchant)->create();

        $otherPending = CreditTransaction::factory()
            ->redemption()
            ->for($merchant)
            ->for($otherMerchantUser, 'merchantUser')
            ->create(['status' => TransactionStatus::PENDING]);

        $this->service->generateQr($merchantUser, 30.00);

        $this->assertEquals(TransactionStatus::PENDING, $otherPending->fresh()->status);
    }

    public function testGenerateQrReturnsUniqueRedemptionCode(): void
    {
        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();

        $first = $this->service->generateQr($merchantUser, 10.00);
        $second = $this->service->generateQr($merchantUser, 20.00);

        $this->assertNotEquals($first->redemption_code, $second->redemption_code);
    }

    public function testRefundRedemptionCreatesRefundTransactionAndRestoresBalance(): void
    {
        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->owner()->for($merchant)->create();
        $customer = User::factory()->create();
        $userCredit = UserCredit::factory()
            ->for($customer)
            ->for($merchant)
            ->withBalance(50)
            ->create(['total_redeemed' => 30]);
        $redemption = CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($merchant)
            ->for($customer)
            ->create([
                'merchant_user_id' => $merchantUser->id,
                'user_credit_id' => $userCredit->id,
                'amount' => 30,
                'balance_before' => 80,
                'balance_after' => 50,
            ]);

        $refund = $this->service->refundRedemption($redemption, $merchantUser, 'Wrong amount.');

        $this->assertEquals(TransactionType::CREDIT_REFUND, $refund->type);
        $this->assertEquals(TransactionStatus::COMPLETED, $refund->status);
        $this->assertEquals('30.00', $refund->amount);
        $this->assertEquals('50.00', $refund->balance_before);
        $this->assertEquals('80.00', $refund->balance_after);
        $this->assertEquals('Wrong amount.', $refund->notes);

        $this->assertEquals(TransactionStatus::REVERSED, $redemption->fresh()->status);

        $userCredit->refresh();
        $this->assertEquals('80.00', $userCredit->balance);
        $this->assertEquals('0.00', $userCredit->total_redeemed);
    }

    public function testRefundRedemptionThrowsIfNotCreditRedemptionType(): void
    {
        $this->expectException(ValidationException::class);

        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();
        $purchase = CreditTransaction::factory()->purchase()->for($merchant)->create();

        $this->service->refundRedemption($purchase, $merchantUser);
    }

    public function testRefundRedemptionThrowsIfNotCompleted(): void
    {
        $this->expectException(ValidationException::class);

        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();
        $redemption = CreditTransaction::factory()
            ->redemption()
            ->for($merchant)
            ->create(['status' => TransactionStatus::REVERSED]);

        $this->service->refundRedemption($redemption, $merchantUser);
    }

    public function testRefundRedemptionThrowsIfUserCreditIdIsNull(): void
    {
        $this->expectException(ValidationException::class);

        $merchant = Merchant::factory()->active()->create();
        $merchantUser = MerchantUser::factory()->for($merchant)->create();
        $redemption = CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($merchant)
            ->create(['user_credit_id' => null]);

        $this->service->refundRedemption($redemption, $merchantUser);
    }
}
