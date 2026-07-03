<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettlementClawbacks\Tables;

use App\Enums\ClawbackStatus;
use App\Support\Filament\Placeholder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettlementClawbacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('merchant.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('originalRedemption.reference_code')
                    ->label('Redemption Ref')
                    ->searchable(),

                TextColumn::make('refundTransaction.created_at')
                    ->label('Refunded At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('amount')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ClawbackStatus $state) => $state->label())
                    ->color(fn (ClawbackStatus $state) => $state->color()),

                TextColumn::make('settlement.reference_code')
                    ->label('Recovered In')
                    ->placeholder(Placeholder::EMPTY)
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Raised At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ClawbackStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
