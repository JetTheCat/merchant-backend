<?php

namespace App\Filament\Resources\PlatformFeeReports\Pages;

use App\Filament\Actions\PlatformFeeReport\GenerateFeeReportAction;
use App\Filament\Resources\PlatformFeeReports\PlatformFeeReportResource;
use Filament\Resources\Pages\ListRecords;

class ListPlatformFeeReports extends ListRecords
{
    protected static string $resource = PlatformFeeReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateFeeReportAction::make(),
        ];
    }
}
