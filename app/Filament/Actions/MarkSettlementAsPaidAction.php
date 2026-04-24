<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Enums\SettlementStatus;
use App\Models\Settlement;
use App\Services\SettlementService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

class MarkSettlementAsPaidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markSettlementAsPaid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $settlementService = app(SettlementService::class);

        $this->label('Mark Settlement as Paid')
            ->icon(Heroicon::CheckCircle)
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription('This will mark the settlement as paid.')
            ->visible(fn (Settlement $record) => in_array($record->status, [SettlementStatus::PENDING, SettlementStatus::PROCESSING]));
        
        $this->schema([
            TextInput::make('paymentReference')
                ->label('Payment Reference')
                ->required(),

            Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
        ]);

        $this->action(function (array $data, Settlement $record) use ($settlementService) {
            $settlementService->markAsPaid($record, $data['paymentReference']);

            if (filled($data['notes'])) {
                $record->update(['notes' => $data['notes']]);
            }
        });
    }
}