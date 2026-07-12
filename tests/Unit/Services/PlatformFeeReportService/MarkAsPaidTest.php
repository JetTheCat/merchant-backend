<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PlatformFeeReportService;

use App\Enums\FeeReportStatus;
use App\Models\PlatformFeeReport;
use App\Services\PlatformFeeReportService;
use Tests\TestCase;

class MarkAsPaidTest extends TestCase
{
    private PlatformFeeReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PlatformFeeReportService::class);
    }

    public function testMarkAsPaidCompletesReport(): void
    {
        $report = PlatformFeeReport::factory()->create();

        $result = $this->service->markAsPaid($report, 'TT1234567890');

        $this->assertEquals(FeeReportStatus::PAID, $result->status);
        $this->assertEquals('TT1234567890', $result->payment_reference);
        $this->assertNotNull($result->paid_at);

        $this->assertDatabaseHas('platform_fee_reports', [
            'id' => $report->id,
            'status' => FeeReportStatus::PAID,
            'payment_reference' => 'TT1234567890',
        ]);
    }
}
