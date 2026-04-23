<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\SettlementStatus;
use App\Enums\TransactionStatus;
use App\Models\CreditTransaction;
use App\Models\Merchant;
use App\Models\Settlement;
use Tests\TestCase;

class GenerateSettlementTest extends TestCase
{
    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::factory()->active()->create([
            'platform_fee_rate' => 0.05,
        ]);
    }

    public function testGeneratesSettlementSuccessfully(): void
    {
        CreditTransaction::factory()->count(3)->purchase()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'paid_amount' => 100.00,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
        ]);

        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Settlement generated successfully.')
            ->expectsOutputToContain('STL-');
    }

    public function testSettlementIsCreatedInDatabase(): void
    {
        CreditTransaction::factory()->count(2)->purchase()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'paid_amount' => 50.00,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 2.50,
        ]);

        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])->assertSuccessful();

        $this->assertDatabaseHas('settlements', [
            'merchant_id' => $this->merchant->id,
            'total_sales' => '100.00',
            'total_fees' => '5.00',
            'net_amount' => '95.00',
            'transaction_count' => 2,
            'status' => SettlementStatus::PENDING,
        ]);
    }

    public function testFailsWhenMerchantNotFound(): void
    {
        $this->artisan('settlements:generate', [
            'merchantId' => '00000000-0000-0000-0000-000000000000',
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('not found');
    }

    public function testFailsWithInvalidDateFormat(): void
    {
        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => '01-04-2026',
            'periodEnd' => '30-04-2026',
        ])
            ->assertFailed()
            ->expectsOutputToContain('Invalid date format');
    }

    public function testFailsWhenPeriodEndIsBeforePeriodStart(): void
    {
        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->subDay()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('Period end date must be on or after period start date');
    }

    public function testFailsWhenMerchantHasNoBankingDetails(): void
    {
        $merchant = Merchant::factory()->active()->withoutBanking()->create();

        $this->artisan('settlements:generate', [
            'merchantId' => $merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('banking details');
    }

    public function testFailsWhenNoUnsettledTransactionsExist(): void
    {
        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('No unsettled credit purchases found');
    }

    public function testDoesNotIncludeTransactionsOutsidePeriod(): void
    {
        CreditTransaction::factory()->purchase()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'paid_amount' => 100.00,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'created_at' => now()->subDays(10),
        ]);

        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->subDays(3)->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('No unsettled credit purchases found');
    }

    public function testDoesNotIncludeAlreadySettledTransactions(): void
    {
        $existingSettlement = Settlement::factory()->for($this->merchant)->create();

        CreditTransaction::factory()->purchase()->for($this->merchant)->create([
            'status' => TransactionStatus::COMPLETED,
            'paid_amount' => 100.00,
            'platform_fee_rate' => 0.05,
            'platform_fee' => 5.00,
            'settlement_id' => $existingSettlement->id,
        ]);

        $this->artisan('settlements:generate', [
            'merchantId' => $this->merchant->id,
            'periodStart' => now()->toDateString(),
            'periodEnd' => now()->toDateString(),
        ])
            ->assertFailed()
            ->expectsOutputToContain('No unsettled credit purchases found');
    }
}
