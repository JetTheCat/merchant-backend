<?php

declare(strict_types=1);

namespace App\Filament\Resources\Merchants\RelationManagers;

use App\Enums\SettlementStatus;
use App\Filament\Actions\DownloadSettlementInvoiceAction;
use App\Support\Filament\Placeholder;
use App\Filament\Actions\MarkSettlementAsPaidAction;
use App\Filament\Resources\Settlements\SettlementResource;
use App\Models\Settlement;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_code')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference_code')
                    ->label('Reference')
                    ->searchable(),

                TextColumn::make('period_start')
                    ->label('Period Start')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('period_end')
                    ->label('Period End')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('transaction_count')
                    ->label('Purchases')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('net_amount')
                    ->label('Net Payout')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SettlementStatus $state) => $state->label())
                    ->color(fn (SettlementStatus $state) => $state->color()),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y, H:i')
                    ->placeholder(Placeholder::EMPTY)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SettlementStatus::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Settlement $record) => SettlementResource::getUrl('view', ['record' => $record])),
                DownloadSettlementInvoiceAction::make(),
                MarkSettlementAsPaidAction::make(),
            ]);
    }
}
