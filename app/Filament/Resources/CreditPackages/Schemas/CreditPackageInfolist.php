<?php

declare(strict_types=1);

namespace App\Filament\Resources\CreditPackages\Schemas;

use App\Models\CreditPackage;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreditPackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('description')
                            ->placeholder(Placeholder::EMPTY)
                            ->columnSpanFull(),

                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),

                Section::make('Pricing')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('credit_amount')
                            ->label('Credit Value')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('selling_price')
                            ->label('Selling Price')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('discount_percentage')
                            ->label('Discount')
                            ->suffix('%')
                            ->numeric(decimalPlaces: 2)
                            ->state(fn (CreditPackage $record) => $record->discount_percentage),
                    ]),

                Section::make('Availability')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('sold_quantity')
                            ->label('Sold'),

                        TextEntry::make('total_quantity')
                            ->label('Total Limit')
                            ->placeholder('Unlimited'),

                        TextEntry::make('purchase_limit_per_user')
                            ->label('Per User Limit')
                            ->placeholder('Unlimited'),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d M Y, H:i'),
                    ]),
            ]);
    }
}
