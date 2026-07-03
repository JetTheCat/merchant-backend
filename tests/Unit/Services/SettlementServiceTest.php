<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\ClawbackStatus;
use App\Enums\SettlementStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use App\Models\SettlementClawback;
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

        $this->merchant = Merchant::factory()->active()->create();
    }

    public function testGenerateForMerchantCreatesSettlement(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->count(3)
            ->create([
                'settlement_amount' => 100.00,
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
            'total_sales' => '300.00',
            'clawback_amount' => '0.00',
            'net_amount' => '300.00',
            'transaction_count' => 3,
            'status' => SettlementStatus::PENDING,
        ]);
    }

    public function testGenerateForMerchantSnapshotsBankingDetails(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->create([
                'settlement_amount' => 100.00,
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
        $transactions = CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->count(2)
            ->create([
                'settlement_amount' => 100.00,
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

        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->create([
                'settlement_amount' => 100.00,
                'settlement_id' => $existingSettlement->id,
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
        CreditTransaction::factory()
            ->redemption()
            ->pending()
            ->for($this->merchant)
            ->create();

        $this->expectException(InvalidArgumentException::class);

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );
    }

    public function testGenerateForMerchantOnlyIncludesTransactionsWithinPeriod(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->create([
                'settlement_amount' => 100.00,
                'created_at' => now()->subDays(10),
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
        $this->expectExceptionMessage('No unsettled credit redemptions found');

        $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );
    }

    public function testClawbackIsRecoveredWhenItFitsWithinTotalSales(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->count(3)
            ->create(['settlement_amount' => 100.00]);

        $clawback = SettlementClawback::factory()
            ->for($this->merchant)
            ->create(['amount' => 100.00]);

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );

        // total_sales = 300, clawback = 100 -> net_amount = 200
        $this->assertEquals('300.00', $settlement->total_sales);
        $this->assertEquals('100.00', $settlement->clawback_amount);
        $this->assertEquals('200.00', $settlement->net_amount);

        $clawback->refresh();
        $this->assertEquals(ClawbackStatus::RECOVERED, $clawback->status);
        $this->assertEquals($settlement->id, $clawback->settlement_id);
    }

    public function testClawbackIsSkippedWhenItExceedsTotalSales(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->create(['settlement_amount' => 100.00]);

        $clawback = SettlementClawback::factory()
            ->for($this->merchant)
            ->create(['amount' => 150.00]);

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );

        // clawback (150) exceeds total_sales (100) — skipped, carries to next cycle
        $this->assertEquals('100.00', $settlement->total_sales);
        $this->assertEquals('0.00', $settlement->clawback_amount);
        $this->assertEquals('100.00', $settlement->net_amount);

        $clawback->refresh();
        $this->assertEquals(ClawbackStatus::PENDING, $clawback->status);
        $this->assertNull($clawback->settlement_id);
    }

    public function testOversizedClawbackSkippedButSmallerSubsequentOneStillRecovered(): void
    {
        CreditTransaction::factory()
            ->redemption()
            ->completed()
            ->for($this->merchant)
            ->create(['settlement_amount' => 100.00]);

        // A is too large, B fits — proves continue rather than break
        [$clawbackA, $clawbackB] = SettlementClawback::factory()
            ->for($this->merchant)
            ->count(2)
            ->sequence(
                ['amount' => 150.00, 'created_at' => now()->subMinute()],
                ['amount' => 80.00]
            )
            ->create();

        $settlement = $this->service->generateForMerchant(
            $this->merchant,
            CarbonImmutable::today(),
            CarbonImmutable::today(),
        );

        // A (150) exceeds total_sales (100) — skipped; B (80) still fits — recovered
        $this->assertEquals('100.00', $settlement->total_sales);
        $this->assertEquals('80.00', $settlement->clawback_amount);
        $this->assertEquals('20.00', $settlement->net_amount);

        $clawbackA->refresh();
        $this->assertEquals(ClawbackStatus::PENDING, $clawbackA->status);
        $this->assertNull($clawbackA->settlement_id);

        $clawbackB->refresh();
        $this->assertEquals(ClawbackStatus::RECOVERED, $clawbackB->status);
        $this->assertEquals($settlement->id, $clawbackB->settlement_id);
    }

    public function testPendingClawbacksAreNotModifiedWhenNoRedemptionsExist(): void
    {
        $clawback = SettlementClawback::factory()
            ->for($this->merchant)
            ->create(['amount' => 100.00]);

        try {
            $this->service->generateForMerchant(
                $this->merchant,
                CarbonImmutable::today(),
                CarbonImmutable::today(),
            );
            $this->fail('Expected an InvalidArgumentException to be thrown.');
        } catch (InvalidArgumentException) {
            $clawback->refresh();
            $this->assertEquals(ClawbackStatus::PENDING, $clawback->status);
            $this->assertNull($clawback->settlement_id);
        }
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
