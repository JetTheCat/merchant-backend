<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlatformFeeReports\Pages;

use App\Filament\Actions\PlatformFeeReport\DownloadPlatformFeeReportAction;
use App\Filament\Actions\PlatformFeeReport\MarkPlatformFeeReportAsPaidAction;
use App\Filament\Resources\PlatformFeeReports\PlatformFeeReportResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPlatformFeeReport extends ViewRecord
{
    protected static string $resource = PlatformFeeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DownloadPlatformFeeReportAction::make(),
            MarkPlatformFeeReportAsPaidAction::make(),
        ];
    }
}
