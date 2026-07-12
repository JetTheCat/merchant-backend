<?php

declare(strict_types=1);

namespace App\Filament\Actions\PlatformFeeReport;

use App\Exceptions\PlatformFeeReport\MissingFirstPeriodStartException;
use App\Services\PlatformFeeReportService;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class GenerateFeeReportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateFeeReport';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Generate Report')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color('primary');

        $platformFeeReportService = app(PlatformFeeReportService::class);

        try {
            $nextPeriod = $platformFeeReportService->nextPeriod();
        } catch (MissingFirstPeriodStartException $exception) {
            $this->disabled()
                ->tooltip($exception->getMessage());

            return;
        }

        $reportingTimezone = config('app.reporting_timezone');
        $isAvailable = CarbonImmutable::now($reportingTimezone)->greaterThanOrEqualTo($nextPeriod['available_from']);

        $this->requiresConfirmation()
            ->modalDescription(sprintf(
                'This will generate the platform fee report for %s to %s.',
                $nextPeriod['period_start']->format('d M Y'),
                $nextPeriod['period_end']->format('d M Y'),
            ))
            ->disabled(! $isAvailable)
            ->tooltip($isAvailable ? null : sprintf(
                'Next report available from %s.',
                $nextPeriod['available_from']->format('d M Y'),
            ));

        $this->action(function () use ($platformFeeReportService, $nextPeriod) {
            $platformFeeReportService->generateForPeriod(
                $nextPeriod['period_start'],
                $nextPeriod['period_end'],
                auth()->user(),
            );
        });
    }
}
