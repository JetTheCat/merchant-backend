<?php

declare(strict_types=1);

namespace App\Filament\Resources\Merchants\RelationManagers;

use App\Filament\Resources\CreditPackages\CreditPackageResource;
use App\Models\CreditPackage;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CreditPackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'creditPackages';

    protected static ?string $title = 'Credit Packages';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
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
                    ->numeric(decimalPlaces: 2),

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
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (CreditPackage $record) => CreditPackageResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
