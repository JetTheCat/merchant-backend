<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PlatformFeeReportService;

use App\Models\PlatformFeeReport;
use App\Services\PlatformFeeReportService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class PreviousUnpaidReportTest extends TestCase
{
    private PlatformFeeReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlatformFeeReportService::class);
    }

    public function testPreviousUnpaidReportReturnsNullWhenNoReportsExist(): void
    {
        $this->assertNull($this->service->previousUnpaidReport());
    }

    public function testPreviousUnpaidReportUsesLatestPeriodEnd(): void
    {
        [, $latestReport] = PlatformFeeReport::factory()
            ->count(2)
            ->sequence(
                [
                    'period_end' => CarbonImmutable::now()->subDay(),
                ],
                [
                    'period_end' => CarbonImmutable::now(),
                ]
            )
            ->create();

        $this->assertTrue($this->service->previousUnpaidReport()->is($latestReport));
    }

    public function testPreviousUnpaidReportReturnsNullWhenLatestReportIsPaid(): void
    {
        PlatformFeeReport::factory()->paid()->create([
            'period_end' => CarbonImmutable::now(),
        ]);

        $this->assertNull($this->service->previousUnpaidReport());
    }

    public function testPreviousUnpaidReportIgnoresSoftDeletedReports(): void
    {
        $deletedReport = PlatformFeeReport::factory()->create([
            'period_end' => CarbonImmutable::now(),
        ]);
        $deletedReport->delete();

        $olderReport = PlatformFeeReport::factory()->create([
            'period_end' => CarbonImmutable::now()->subDays(14),
        ]);

        $this->assertTrue($this->service->previousUnpaidReport()->is($olderReport));
    }
}
