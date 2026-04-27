<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Tables;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference_code')
                    ->searchable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state) => $state->label()),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state) => $state->label())
                    ->color(fn (TransactionStatus $state) => $state->color()),

                TextColumn::make('merchant.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Credits')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('platform_fee')
                    ->label('Platform Fee')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(fn () => collect(TransactionType::cases())
                        ->mapWithKeys(fn (TransactionType $type) => [$type->value => $type->label()])
                        ->all()),

                SelectFilter::make('status')
                    ->options(fn () => collect(TransactionStatus::cases())
                        ->mapWithKeys(fn (TransactionStatus $status) => [$status->value => $status->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
