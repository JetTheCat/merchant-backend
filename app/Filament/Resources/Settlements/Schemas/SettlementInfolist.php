<?php

declare(strict_types=1);

namespace App\Filament\Resources\Settlements\Schemas;

use App\Enums\SettlementStatus;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettlementInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Settlement Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reference_code')
                            ->label('Reference'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (SettlementStatus $state) => $state->label())
                            ->color(fn (SettlementStatus $state) => $state->color()),

                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('period_start')
                            ->label('Period Start')
                            ->date('d M Y'),

                        TextEntry::make('period_end')
                            ->label('Period End')
                            ->date('d M Y'),

                        TextEntry::make('created_at')
                            ->label('Generated At')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Financial Summary')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('total_sales')
                            ->label('Total Sales')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('net_amount')
                            ->label('Net Payout')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('transaction_count')
                            ->label('Redemptions'),
                    ]),

                Section::make('Banking Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('bank_name')
                            ->label('Bank')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('bank_account_name')
                            ->label('Account Name')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('bank_account_number')
                            ->label('Account Number')
                            ->placeholder(Placeholder::EMPTY),
                    ]),

                Section::make('Payment')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('payment_reference')
                            ->label('Payment Reference')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('paid_at')
                            ->label('Paid At')
                            ->dateTime('d M Y, H:i')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('notes')
                            ->placeholder(Placeholder::EMPTY)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
