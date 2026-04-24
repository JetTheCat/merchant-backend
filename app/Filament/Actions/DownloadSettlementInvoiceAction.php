<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Settlement;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;

class DownloadSettlementInvoiceAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'downloadSettlementInvoice';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download Invoice')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->url(fn (Settlement $record) => route('admin.settlements.invoice', $record))
            ->openUrlInNewTab();
    }
}
