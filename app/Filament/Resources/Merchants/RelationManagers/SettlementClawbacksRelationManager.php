<?php

declare(strict_types=1);

namespace App\Filament\Resources\Merchants\RelationManagers;

use App\Enums\ClawbackStatus;
use App\Filament\Resources\SettlementClawbacks\SettlementClawbackResource;
use App\Models\SettlementClawback;
use App\Support\Filament\Placeholder;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SettlementClawbacksRelationManager extends RelationManager
{
    protected static string $relationship = 'clawbacks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
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
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ClawbackStatus::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (SettlementClawback $record) => SettlementClawbackResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
