<?php

namespace App\Filament\Resources\Merchants\Schemas;

use App\Enums\MerchantStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),

                        TextEntry::make('business_name')
                            ->label('Business Name'),

                        TextEntry::make('category.name')
                            ->label('Category')
                            ->badge(),

                        TextEntry::make('business_registration_number')
                            ->label('Business Registration No.')
                            ->default('—'),

                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (MerchantStatus $state) => $state->label())
                            ->color(fn (MerchantStatus $state) => match ($state) {
                                MerchantStatus::ACTIVE => 'success',
                                MerchantStatus::SUSPENDED => 'danger',
                            }),

                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->default('—'),
                    ]),

                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('phone'),

                        TextEntry::make('website')
                            ->default('—'),
                    ]),

                Section::make('Location')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('address')
                            ->columnSpanFull(),

                        TextEntry::make('city'),

                        TextEntry::make('state'),

                        TextEntry::make('postcode'),

                        TextEntry::make('latitude')
                            ->default('—'),

                        TextEntry::make('longitude')
                            ->default('—'),
                    ]),

                Section::make('Business Settings')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('formatted_platform_fee_rate')
                            ->label('Platform Fee Rate'),

                        TextEntry::make('settlement_cycle')
                            ->label('Settlement Cycle')
                            ->formatStateUsing(fn ($state) => $state instanceof \App\Enums\SettlementCycle ? $state->label() : $state),

                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime('d M Y'),
                    ]),

                Section::make('Banking Details')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('bank_name')
                            ->label('Bank Name')
                            ->default('—'),

                        TextEntry::make('bank_account_number')
                            ->label('Account Number')
                            ->default('—')
                            ->copyable(),

                        TextEntry::make('bank_account_name')
                            ->label('Account Name')
                            ->default('—'),
                    ]),
            ]);
    }
}
