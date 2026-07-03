<?php

declare(strict_types=1);

namespace App\Filament\Resources\SettlementClawbacks\Schemas;

use App\Enums\ClawbackStatus;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettlementClawbackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Clawback Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('merchant.name')
                            ->label('Merchant'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (ClawbackStatus $state) => $state->label())
                            ->color(fn (ClawbackStatus $state) => $state->color()),

                        TextEntry::make('amount')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('created_at')
                            ->label('Raised At')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Refund Trail')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('originalRedemption.reference_code')
                            ->label('Original Redemption'),

                        TextEntry::make('originalRedemption.created_at')
                            ->label('Redeemed At')
                            ->dateTime('d M Y, H:i'),

                        TextEntry::make('refundTransaction.reference_code')
                            ->label('Refund Transaction'),

                        TextEntry::make('refundTransaction.created_at')
                            ->label('Refunded At')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Recovery')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('settlement.reference_code')
                            ->label('Recovered In Settlement')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('settlement.period_start')
                            ->label('Settlement Period Start')
                            ->date('d M Y')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('settlement.period_end')
                            ->label('Settlement Period End')
                            ->date('d M Y')
                            ->placeholder(Placeholder::EMPTY),
                    ]),
            ]);
    }
}
