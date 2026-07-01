<?php

declare(strict_types=1);

namespace Tests\Unit\Services\RedemptionService;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\MerchantUser;
use App\Services\RedemptionService;
use Tests\TestCase;

class GenerateFallbackCodeTest extends TestCase
{
    private RedemptionService $service;

    private Merchant $merchant;

    private MerchantUser $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RedemptionService::class);

        $this->merchant = Merchant::factory()->active()->create();
        $this->merchantUser = MerchantUser::factory()->for($this->merchant)->create();
    }

    public function testGenerateFallbackCodeCreatesPendingTransaction(): void
    {
        $transaction = $this->service->generateFallbackCode($this->merchantUser, 50.00);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'merchant_id' => $this->merchant->id,
            'merchant_user_id' => $this->merchantUser->id,
            'type' => TransactionType::CREDIT_REDEMPTION,
            'status' => TransactionStatus::PENDING,
            'amount' => '50.00',
        ]);

        $this->assertNotNull($transaction->fallback_code);
        $this->assertNotNull($transaction->expires_at);
        $this->assertTrue($transaction->expires_at->isFuture());
    }

    public function testGenerateFallbackCodeCancelsPreviousPendingForSameMerchantUser(): void
    {
        $existing = CreditTransaction::factory()
            ->redemption()
            ->for($this->merchant)
            ->for($this->merchantUser)
            ->create(['status' => TransactionStatus::PENDING]);

        $this->service->generateFallbackCode($this->merchantUser, 30.00);

        $this->assertEquals(TransactionStatus::CANCELLED, $existing->fresh()->status);
    }

    public function testGenerateFallbackCodeDoesNotCancelPendingForOtherMerchantUsers(): void
    {
        $otherMerchantUser = MerchantUser::factory()->for($this->merchant)->create();

        $otherPending = CreditTransaction::factory()
            ->redemption()
            ->for($this->merchant)
            ->for($otherMerchantUser, 'merchantUser')
            ->create(['status' => TransactionStatus::PENDING]);

        $this->service->generateFallbackCode($this->merchantUser, 30.00);

        $this->assertEquals(TransactionStatus::PENDING, $otherPending->fresh()->status);
    }

    public function testGenerateFallbackCodeReturnsUniqueCode(): void
    {
        $secondMerchantUser = MerchantUser::factory()->for($this->merchant)->create();

        $first = $this->service->generateFallbackCode($this->merchantUser, 10.00);
        $second = $this->service->generateFallbackCode($secondMerchantUser, 20.00);

        $this->assertNotEquals($first->fallback_code, $second->fallback_code);
    }
}
