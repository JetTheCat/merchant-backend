<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'userCredits';

    protected static ?string $title = 'Credit Balances';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('merchant.name')
            ->defaultSort('balance', 'desc')
            ->columns([
                TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('balance')
                    ->label('Balance (RM)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('total_purchased')
                    ->label('Total Purchased (RM)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('total_redeemed')
                    ->label('Total Redeemed (RM)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('last_purchase_at')
                    ->label('Last Purchase')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),

                TextColumn::make('last_redemption_at')
                    ->label('Last Redemption')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ]);
    }
}
