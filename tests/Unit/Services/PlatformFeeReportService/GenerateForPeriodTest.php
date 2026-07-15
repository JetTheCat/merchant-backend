<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PlatformFeeReportService;

use App\Enums\PlatformFeeReportStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\PlatformFeeReport\OverlappingPeriodException;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\PlatformFeeReport;
use App\Models\User;
use App\Services\PlatformFeeReportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GenerateForPeriodTest extends TestCase
{
    private PlatformFeeReportService $service;

    private User $generatedBy;

    private const string KUALA_LUMPUR_TIMEZONE = 'Asia/Kuala_Lumpur';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlatformFeeReportService::class);

        $this->generatedBy = User::factory()->create();
    }

    #[DataProvider('providesOverlappingPeriodScenarios')]
    public function testGenerateForPeriodThrowsWhenPeriodOverlapsExistingReport(
        int $existingStartOffsetDays,
        int $existingEndOffsetDays,
    ): void {
        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->expectException(OverlappingPeriodException::class);
        $this->expectExceptionMessage(
            sprintf(
                'A fee report already exists for a period overlapping %s to %s.',
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d'),
            )
        );

        PlatformFeeReport::factory()->create([
            'period_start' => $periodStart->addDays($existingStartOffsetDays),
            'period_end' => $periodStart->addDays($existingEndOffsetDays),
        ]);

        $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);
    }

    public static function providesOverlappingPeriodScenarios(): array
    {
        return [
            'existing report fully contains new period' => [
                'existingStartOffsetDays' => -5,
                'existingEndOffsetDays' => 18,
            ],
            'new period fully contains existing report' => [
                'existingStartOffsetDays' => 2,
                'existingEndOffsetDays' => 8,
            ],
            'existing report boundary touches new period start' => [
                'existingStartOffsetDays' => -5,
                'existingEndOffsetDays' => 0,
            ],
            'existing report boundary touches new period end' => [
                'existingStartOffsetDays' => 13,
                'existingEndOffsetDays' => 18,
            ],
            'existing report partially overlaps start of new period' => [
                'existingStartOffsetDays' => -5,
                'existingEndOffsetDays' => 3,
            ],
            'existing report partially overlaps end of new period' => [
                'existingStartOffsetDays' => 7,
                'existingEndOffsetDays' => 18,
            ],
        ];
    }

    public function testGenerateForPeriodSucceedsWhenPeriodIsAdjacentButNotOverlapping(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        PlatformFeeReport::factory()->create([
            'period_start' => $periodStart->subDays(10),
            'period_end' => $periodStart->subDay(),
        ]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertInstanceOf(PlatformFeeReport::class, $report);
    }

    public function testGenerateForPeriodSucceedsWhenNoExistingReports(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertInstanceOf(PlatformFeeReport::class, $report);
    }

    public function testGenerateForPeriodIgnoresSoftDeletedReportsInOverlapCheck(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $existingReport = PlatformFeeReport::factory()->create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
        $existingReport->delete();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertInstanceOf(PlatformFeeReport::class, $report);
    }

    public function testGenerateForPeriodConvertsLocalPeriodStartToUtcBeforeQueryingTransactions(): void
    {
        Storage::fake();

        // 1:00 am local time (Asia/Kuala_Lumpur) is 5:00 pm UTC the previous day
        $kualaLumpurTime = CarbonImmutable::now(self::KUALA_LUMPUR_TIMEZONE)->setHour(1);

        $this->createPurchaseTransaction(['created_at' => $kualaLumpurTime->utc()]);

        $periodStart = $kualaLumpurTime;
        $periodEnd = $kualaLumpurTime->addDays(13);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('40.00', $report->total_gmv);
        $this->assertSame('1.00', $report->total_platform_fees);
        $this->assertSame('2.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodExcludesTransactionOnDayBeforePeriodStart(): void
    {
        Storage::fake();

        $periodStart = CarbonImmutable::now(self::KUALA_LUMPUR_TIMEZONE)->startOfDay();
        $periodEnd = $periodStart->addDays(13);

        // 11:59 pm local time the day before periodStart, one minute before the true cutoff
        $excludedTime = $periodStart->subDay()->setTime(23, 59);

        $this->createPurchaseTransaction(['created_at' => $excludedTime->utc()]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('0.00', $report->total_gmv);
        $this->assertSame('0.00', $report->total_platform_fees);
        $this->assertSame('0.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodExcludesTransactionOnDayAfterPeriodEnd(): void
    {
        Storage::fake();

        $periodStart = CarbonImmutable::now(self::KUALA_LUMPUR_TIMEZONE)->startOfDay();
        $periodEnd = $periodStart->addDays(13);

        // 6:00 am local time (Asia/Kuala_Lumpur) is 10:00 pm UTC the previous day
        $excludedTime = $periodEnd->addDay()->setTime(6, 0);

        $this->createPurchaseTransaction(['created_at' => $excludedTime->utc()]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('0.00', $report->total_gmv);
        $this->assertSame('0.00', $report->total_platform_fees);
        $this->assertSame('0.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodOnlyIncludesCreditPurchaseTransactions(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();

        CreditTransaction::factory()
            ->redemption()
            ->create();

        CreditTransaction::factory()
            ->refund()
            ->create();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('40.00', $report->total_gmv);
        $this->assertSame('1.00', $report->total_platform_fees);
        $this->assertSame('2.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodOnlyIncludesCompletedTransactions(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();
        $this->createPurchaseTransaction(['status' => TransactionStatus::PENDING]);
        $this->createPurchaseTransaction(['status' => TransactionStatus::FAILED]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('40.00', $report->total_gmv);
        $this->assertSame('1.00', $report->total_platform_fees);
        $this->assertSame('2.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodDefaultsProcessingFeeToZeroWhenNoPaymentRecord(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        CreditTransaction::factory()
            ->purchase()
            ->create([
                'amount' => 50,
                'paid_amount' => 40,
                'platform_fee' => 1,
                'platform_fee_rate' => 0.025,
            ]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('40.00', $report->total_gmv);
        $this->assertSame('1.00', $report->total_platform_fees);
        $this->assertSame('0.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodCsvHasCorrectHeaderRow(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $rows = $this->readCsvRows($report->file_path);

        $this->assertSame([
            sprintf('Date (%s)', config('app.reporting_timezone')),
            'Merchant ID',
            'Merchant Name',
            'Payment Method',
            'Paid Amount (RM)',
            'Platform Fee Rate',
            'Platform Fee (RM)',
            'Processing Fee (RM)',
        ], $rows[0]);
    }

    public function testGenerateForPeriodCsvHasCorrectTransactionRowValues(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $transaction = $this->createPurchaseTransaction()->fresh();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $rows = $this->readCsvRows($report->file_path);

        $expectedDate = $transaction->created_at->copy()
            ->setTimezone(config('app.reporting_timezone'))
            ->format('Y-m-d H:i:s');

        $this->assertSame([
            $expectedDate,
            $transaction->merchant_id,
            $transaction->merchant->name,
            'card',
            '40.00',
            '2.50%',
            '1.00',
            '2.00',
        ], $rows[1]);
    }

    public function testGenerateForPeriodCsvAppendsSummarySectionAfterTransactionRows(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $rows = $this->readCsvRows($report->file_path);
        $summaryRows = array_slice($rows, -2);

        $this->assertSame(['Total GMV', 'Total Platform Fees (RM)', 'Total Processing Costs'], $summaryRows[0]);
        $this->assertSame(['40.00', '1.00', '2.00'], $summaryRows[1]);
    }

    public function testGenerateForPeriodReportTotalsMatchSumOfTransactions(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction(['paid_amount' => 40, 'platform_fee' => 1]);
        $this->createPurchaseTransaction(['paid_amount' => 60, 'platform_fee' => 3]);
        $this->createPurchaseTransaction(['paid_amount' => 25.50, 'platform_fee' => 0.75]);

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame('125.50', $report->total_gmv);
        $this->assertSame('4.75', $report->total_platform_fees);
        $this->assertSame('6.00', $report->total_processing_costs);
    }

    public function testGenerateForPeriodReturnsReportWithPendingStatus(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame(PlatformFeeReportStatus::PENDING, $report->status);
    }

    public function testGenerateForPeriodStoresReportFilePathFromStorageService(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $expectedPath = sprintf(
            'fee-reports/platform-fee-report_%s_%s.csv',
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d'),
        );

        $this->assertSame($expectedPath, $report->file_path);
        Storage::assertExists($report->file_path);
    }

    public function testGenerateForPeriodStoresReportGeneratedByAsUserId(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        $this->createPurchaseTransaction();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $this->assertSame($this->generatedBy->id, $report->generated_by);
    }

    public function testGenerateForPeriodPersistsSnapshotTotalsMatchingCsvSummary(): void
    {
        Storage::fake();

        $periodStart = $this->nowInReportingTimezone();
        $periodEnd = $periodStart->addDays(13);

        CreditTransaction::factory()
            ->purchase()
            ->has(
                Payment::factory()
                    ->succeeded()
                    ->state([
                        'processing_fee' => 2.00,
                    ])
            )
            ->count(2)
            ->sequence(
                [
                    'amount' => 100,
                    'paid_amount' => 90,
                    'platform_fee' => 3,
                    'platform_fee_rate' => 0.025,
                ],
                [
                    'amount' => 50,
                    'paid_amount' => 40,
                    'platform_fee' => 1,
                    'platform_fee_rate' => 0.025,
                ]
            )
            ->create();

        $report = $this->service->generateForPeriod($periodStart, $periodEnd, $this->generatedBy);

        $rows = $this->readCsvRows($report->file_path);
        $summaryRow = array_slice($rows, -1)[0];

        $this->assertSame($report->total_gmv, $summaryRow[0]);
        $this->assertSame($report->total_platform_fees, $summaryRow[1]);
        $this->assertSame($report->total_processing_costs, $summaryRow[2]);
    }

    private function nowInReportingTimezone(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.reporting_timezone'));
    }

    private function readCsvRows(string $path): array
    {
        return iterator_to_array(Reader::fromString(Storage::get($path)), false);
    }

    private function createPurchaseTransaction(array $overrides = []): CreditTransaction
    {
        return CreditTransaction::factory()
            ->purchase()
            ->has(
                Payment::factory()
                    ->succeeded()
                    ->state([
                        'processing_fee' => 2.00,
                    ])
            )
            ->create(array_merge([
                'amount' => 50,
                'paid_amount' => 40,
                'platform_fee' => 1,
                'platform_fee_rate' => 0.025,
            ], $overrides));
    }
}
