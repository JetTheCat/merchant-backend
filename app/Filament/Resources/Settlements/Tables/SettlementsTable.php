<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settlements\Tables;

use App\Enums\SettlementStatus;
use App\Filament\Actions\DownloadSettlementInvoiceAction;
use App\Support\Filament\Placeholder;
use App\Filament\Actions\MarkSettlementAsPaidAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettlementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference_code')
                    ->searchable(),

                TextColumn::make('merchant.name')
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

                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
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

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SettlementStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                DownloadSettlementInvoiceAction::make(),
                MarkSettlementAsPaidAction::make(),
            ])
            ->toolbarActions([]);
    }
}
