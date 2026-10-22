<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\FeeReportStatus;
use App\Models\PlatformFeeReport;
use App\Services\PlatformFeeReportService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class MarkPlatformFeeReportAsPaidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markPlatformFeeReportAsPaid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $platformFeeReportService = app(PlatformFeeReportService::class);

        $this->label('Mark as Paid')
            ->icon(Heroicon::CheckCircle)
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription('This will mark the platform fee report as paid.')
            ->visible(fn (PlatformFeeReport $record) => $record->status === FeeReportStatus::PENDING);

        $this->schema([
            TextInput::make('paymentReference')
                ->label('Payment Reference')
                ->required(),
        ]);

        $this->action(function (array $data, PlatformFeeReport $record) use ($platformFeeReportService) {
            $platformFeeReportService->markAsPaid($record, $data['paymentReference']);
        });
    }
}
