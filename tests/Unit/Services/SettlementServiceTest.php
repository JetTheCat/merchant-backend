<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\SettlementStatus;
use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class SettlementServiceTest extends TestCase
{
    private SettlementService $service;
    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SettlementService::class);

        $this->merchant = Merchant::factory()->active()->create([
            'platform_fee_rate' => 0.05,
        ]);
    }

    public function testGenerateForMerchantCreatesSettlement(): void
    {
        CreditTransaction::factory()->count(3)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'amount' => 100.00,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'redeemed_at' => now(),
        ]);

        $date = CarbonImmutable::today();

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            $date,
            $date,
        );

        $this->assertInstanceOf(Settlement::class, $settlement);
        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'merchant_id' => $this->merchant->id,
            'period_start' => $date->toDateString(),
            'period_end' => $date->toDateString(),
            'total_redemptions' => '300.00',
            'total_fees' => '15.00',
            'net_amount' => '285.00',
            'redemption_count' => 3,
            'status' => SettlementStatus::PENDING,
        ]);
    }

    public function testGenerateForMerchantSnapshotsBankingDetails(): void
    {
        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'redeemed_at' => now(),
        ]);

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );

        $this->assertEquals($this->merchant->bank_name, $settlement->bank_name);
        $this->assertEquals($this->merchant->bank_account_number, $settlement->bank_account_number);
        $this->assertEquals($this->merchant->bank_account_name, $settlement->bank_account_name);
    }

    public function testGenerateForMerchantSetsSettlementIdOnTransactions(): void
    {
        $transactions = CreditTransaction::factory()->count(2)->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'redeemed_at' => now(),
        ]);

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );

        foreach ($transactions as $transaction) {
            $this->assertEquals($settlement->id, $transaction->fresh()->settlement_id);
        }
    }

    public function testGenerateForMerchantOnlyIncludesUnsettledTransactions(): void
    {
        $existingSettlement = Settlement::factory()->for($this->merchant)->create();

        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'settlement_id' => $existingSettlement->id,
            'redeemed_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );
    }

    public function testGenerateForMerchantOnlyIncludesCompletedTransactions(): void
    {
        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::PENDING,
            'redeemed_at' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );
    }

    public function testGenerateForMerchantOnlyIncludesTransactionsWithinPeriod(): void
    {
        CreditTransaction::factory()->redemption()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'redeemed_at' => now()->subDays(10),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today()->subDays(3),
            CarbonImmutable::today(),
        );
    }

    public function testGenerateForMerchantThrowsWhenNoBankingDetails(): void
    {
        $merchant = Merchant::factory()->active()->withoutBanking()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('banking details');

        $this->service->generateForMerchant($merchant, CarbonImmutable::today(), CarbonImmutable::today());
    }

    public function testGenerateForMerchantThrowsWhenNoUnsettledTransactions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No unsettled transactions found');

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );
    }

    public function testMarkAsPaidCompletesSettlement(): void
    {
        $settlement = Settlement::factory()->for($this->merchant)->create();

        $result = $this->service->markAsPaid($settlement, 'TT1234567890');

        $this->assertEquals(SettlementStatus::COMPLETED, $result->status);
        $this->assertEquals('TT1234567890', $result->payment_reference);
        $this->assertNotNull($result->paid_at);

        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'status' => SettlementStatus::COMPLETED,
            'payment_reference' => 'TT1234567890',
        ]);
    }
}
