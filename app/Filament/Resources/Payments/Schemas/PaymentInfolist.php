<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Support\Filament\Placeholder;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('stripe_payment_intent_id')
                            ->label('Payment Intent ID')
                            ->copyable(),

                        TextEntry::make('stripe_charge_id')
                            ->label('Charge ID')
                            ->placeholder(Placeholder::EMPTY)
                            ->copyable(),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                            ->color(fn (PaymentStatus $state) => $state->color()),

                        TextEntry::make('payment_method')
                            ->label('Method')
                            ->formatStateUsing(fn (?PaymentType $state) => $state?->label() ?? Placeholder::EMPTY),

                        TextEntry::make('currency')
                            ->formatStateUsing(fn ($state) => strtoupper($state->value)),

                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('d M Y, H:i'),
                    ]),

                Section::make('Amount')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('amount')
                            ->prefix('RM ')
                            ->numeric(decimalPlaces: 2),

                        TextEntry::make('paid_at')
                            ->label('Paid At')
                            ->dateTime('d M Y, H:i')
                            ->placeholder(Placeholder::EMPTY),

                        TextEntry::make('failure_reason')
                            ->label('Failure Reason')
                            ->placeholder(Placeholder::EMPTY)
                            ->columnSpanFull(),
                    ]),

                Section::make('Customer')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.full_name')
                            ->label('Name'),

                        TextEntry::make('user.email')
                            ->label('Email'),

                        TextEntry::make('transaction.reference_code')
                            ->label('Transaction Reference')
                            ->placeholder(Placeholder::EMPTY),
                    ]),
            ]);
    }
}
