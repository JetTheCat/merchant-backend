<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditPackages\Tables;

use App\Models\CreditPackage;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CreditPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('merchant.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('credit_amount')
                    ->label('Credits')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('selling_price')
                    ->label('Price')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('discount_percentage')
                    ->label('Discount')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->state(fn (CreditPackage $record) => $record->discount_percentage),

                TextColumn::make('sold_quantity')
                    ->label('Sold')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total_quantity')
                    ->label('Limit')
                    ->numeric()
                    ->placeholder('Unlimited'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('merchant')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
