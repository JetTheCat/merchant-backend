<?php

declare(strict_types=1);

namespace App\Filament\Actions\PlatformFeeReport;

use App\Models\PlatformFeeReport;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadFeeReportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadFeeReport';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download Report')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (PlatformFeeReport $record) => route('admin.platform-fee-reports.download', $record))
            ->openUrlInNewTab();
    }
}
