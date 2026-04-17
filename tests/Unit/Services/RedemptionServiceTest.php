<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Services\RedemptionService;
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
}
