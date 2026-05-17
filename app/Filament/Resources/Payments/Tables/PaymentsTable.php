<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Support\Filament\Placeholder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('stripe_payment_intent_id')
                    ->label('Payment Intent')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('user.email')
                    ->label('Customer')
                    ->searchable(),

                TextColumn::make('amount')
                    ->prefix('RM ')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus $state) => $state->label())
                    ->color(fn (PaymentStatus $state) => $state->color()),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (?PaymentType $state) => $state?->label() ?? Placeholder::EMPTY),

                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y, H:i')
                    ->placeholder(Placeholder::EMPTY)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => collect(PaymentStatus::cases())
                        ->mapWithKeys(fn (PaymentStatus $status) => [$status->value => $status->label()])
                        ->all()),

                SelectFilter::make('payment_method')
                    ->label('Method')
                    ->options(fn () => collect(PaymentType::cases())
                        ->mapWithKeys(fn (PaymentType $type) => [$type->value => $type->label()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
