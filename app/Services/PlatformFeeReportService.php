<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Storage\PlatformFeeReportStorageInterface;
use App\Enums\PlatformFeeReportStatus;
use App\Exceptions\PlatformFeeReport\MissingFirstPeriodStartException;
use App\Exceptions\PlatformFeeReport\OverlappingPeriodException;
use App\Models\CreditTransaction;
use App\Models\PlatformFeeReport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use League\Csv\Writer;

class PlatformFeeReportService
{
    public function __construct(private readonly PlatformFeeReportStorageInterface $feeReportStorage) {}

    /**
     * Only the Y-m-d portion of $from/$to is used, treated as a calendar day in
     * reporting_timezone — the instant/timezone each value carries is otherwise ignored.
     */
    public function generateForPeriod(CarbonImmutable $from, CarbonImmutable $to, User $generatedBy): PlatformFeeReport
    {
        $reportingTimezone = config('app.reporting_timezone');

        // periodStart/periodEnd are calendar days in reporting_timezone; queryFrom/queryTo are
        // the same instants in UTC, for comparing against created_at. The format('Y-m-d') is
        // required — see "Timezone-safe date conversion" in CLAUDE.md.
        $periodStart = CarbonImmutable::parse($from->format('Y-m-d'), $reportingTimezone)->startOfDay();
        $periodEnd = CarbonImmutable::parse($to->format('Y-m-d'), $reportingTimezone)->endOfDay();

        $queryFrom = $periodStart->utc();
        $queryTo = $periodEnd->utc();

        $this->assertPeriodIsAvailable($periodStart, $periodEnd);

        $filename = sprintf(
            'platform-fee-report_%s_%s.csv',
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d')
        );

        $writer = Writer::fromString();
        $writer->setEndOfLine("\r\n");
        $writer->setEscape('');

        $writer->insertOne($this->tableHeader());

        $totalGmv = '0.00';
        $totalPlatformFees = '0.00';
        $totalProcessingCosts = '0.00';

        CreditTransaction::with([
            'payment',
            'merchant',
        ])
            ->purchases()
            ->completed()
            ->where('created_at', '>=', $queryFrom)
            ->where('created_at', '<=', $queryTo)
            ->chunkById(100, function (Collection $purchases) use ($writer, &$totalGmv, &$totalPlatformFees, &$totalProcessingCosts) {
                foreach ($purchases as $purchase) {
                    $processingFee = $purchase->payment?->processing_fee ?? '0.00';

                    $writer->insertOne([
                        $purchase->created_at->copy()->setTimezone(config('app.reporting_timezone'))->format('Y-m-d H:i:s'),
                        $purchase->merchant_id,
                        $purchase->merchant->name,
                        $purchase->payment?->payment_method?->value,
                        $purchase->paid_amount,
                        mul($purchase->platform_fee_rate, '100').'%',
                        $purchase->platform_fee,
                        $processingFee,
                    ]);

                    $totalGmv = add($totalGmv, $purchase->paid_amount);
                    $totalPlatformFees = add($totalPlatformFees, $purchase->platform_fee);
                    $totalProcessingCosts = add($totalProcessingCosts, $processingFee);
                }
            });

        $writer->insertOne([]);
        $writer->insertOne($this->summaryHeader());
        $writer->insertOne([
            $totalGmv,
            $totalPlatformFees,
            $totalProcessingCosts,
        ]);

        $storedPath = $this->feeReportStorage->store($filename, $writer->toString());

        return PlatformFeeReport::create([
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'file_path' => $storedPath,
            'total_gmv' => $totalGmv,
            'total_platform_fees' => $totalPlatformFees,
            'total_processing_costs' => $totalProcessingCosts,
            'status' => PlatformFeeReportStatus::PENDING,
            'generated_by' => $generatedBy->id,
        ]);
    }

    /**
     * @return array{period_start: CarbonImmutable, period_end: CarbonImmutable, available_from: CarbonImmutable}
     */
    public function nextPeriod(): array
    {
        $reportingTimezone = config('app.reporting_timezone');

        $lastReport = PlatformFeeReport::latest('period_end')->first();

        $periodStart = $lastReport
            ? CarbonImmutable::parse($lastReport->period_end->format('Y-m-d'), $reportingTimezone)->addDay()->startOfDay()
            : $this->firstPeriodStart($reportingTimezone);

        $periodEnd = $periodStart->addDays(6)->endOfDay();

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'available_from' => $periodEnd->addDays(config('platform-fee-report.availability_buffer_days'))->startOfDay(),
        ];
    }

    private function firstPeriodStart(string $reportingTimezone): CarbonImmutable
    {
        $firstPeriodStart = config('platform-fee-report.first_period_start');

        if ($firstPeriodStart === null) {
            throw new MissingFirstPeriodStartException(
                'No platform fee reports exist yet. Set PLATFORM_FEE_REPORT_FIRST_PERIOD_START to generate the first report.'
            );
        }

        return CarbonImmutable::createFromFormat('Y-m-d', $firstPeriodStart, $reportingTimezone)->startOfDay();
    }

    public function previousUnpaidReport(): ?PlatformFeeReport
    {
        $latest = PlatformFeeReport::latest('period_end')->first();

        return $latest?->status === PlatformFeeReportStatus::PENDING ? $latest : null;
    }

    public function markAsPaid(PlatformFeeReport $platformFeeReport, string $paymentReference): PlatformFeeReport
    {
        $platformFeeReport->update([
            'status' => PlatformFeeReportStatus::PAID,
            'payment_reference' => $paymentReference,
            'paid_at' => now(),
        ]);

        return $platformFeeReport->fresh();
    }

    private function tableHeader(): array
    {
        return [
            sprintf('Date (%s)', config('app.reporting_timezone')),
            'Merchant ID',
            'Merchant Name',
            'Payment Method',
            'Paid Amount (RM)',
            'Platform Fee Rate',
            'Platform Fee (RM)',
            'Processing Fee (RM)',
        ];
    }

    private function summaryHeader(): array
    {
        return [
            'Total GMV',
            'Total Platform Fees (RM)',
            'Total Processing Costs',
        ];
    }

    private function assertPeriodIsAvailable(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): void
    {
        $overlaps = PlatformFeeReport::overlapping(
            $periodStart->format('Y-m-d'),
            $periodEnd->format('Y-m-d'),
        )->exists();

        if ($overlaps) {
            throw new OverlappingPeriodException(sprintf(
                'A fee report already exists for a period overlapping %s to %s.',
                $periodStart->format('Y-m-d'),
                $periodEnd->format('Y-m-d'),
            ));
        }
    }
}
