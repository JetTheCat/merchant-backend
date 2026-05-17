<?php

declare(strict_types=1);

namespace App\Filament\Resources\Transactions\Schemas;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Transaction')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference_code')
                            ->label('Reference'),

                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (TransactionType $state) => $state->label()),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (TransactionStatus $state) => $state->label())
                            ->color(fn (TransactionStatus $state) => $state->color()),

                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Parties')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.full_name')
                            ->label('Customer'),

                        TextEntry::make('user.email')
                            ->label('Customer Email'),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('merchantUser.name')
                            ->label('Staff')
                            ->placeholder(Placeholder::EMPTY),
                    ]),

                Section::make('Amounts')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('amount')
                            ->label('Credits')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('paid_amount')
                            ->label('Amount Paid')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('balance_before')
                            ->label('Balance Before')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('balance_after')
                            ->label('Balance After')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('platform_fee_rate')
                            ->label('Fee Rate')
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state * 100, 2).'%' : Placeholder::EMPTY),

                        TextEntry::make('platform_fee')
                            ->label('Platform Fee')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2)
                            ->placeholder(Placeholder::EMPTY),
                    ]),

                Section::make('Related')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('creditPackage.name')
                            ->label('Credit Package')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('settlement.reference_code')
                            ->label('Settlement')
                            ->placeholder(Placeholder::EMPTY),
                    ]),
            ]);
    }
}
