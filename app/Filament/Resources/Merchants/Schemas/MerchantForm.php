<?php

namespace App\Filament\Resources\Merchants\Schemas;

use App\Enums\MerchantStatus;
use App\Enums\SettlementCycle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('business_name')
                            ->label('Business Name')
                            ->required()
                            ->maxLength(255),

                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('business_registration_number')
                            ->label('Business Registration No.')
                            ->maxLength(255),

                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(3)
                            ->maxLength(65535),
                    ]),

                Section::make('Contact')
                    ->columns(2)
                    ->schema([
                        TextInput::make('phone')
                            ->tel()
                            ->required()
                            ->maxLength(20),

                        TextInput::make('website')
                            ->url()
                            ->maxLength(255),
                    ]),

                Section::make('Location')
                    ->columns(3)
                    ->schema([
                        Textarea::make('address')
                            ->required()
                            ->columnSpanFull()
                            ->rows(2),

                        TextInput::make('city')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('state')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('postcode')
                            ->required()
                            ->maxLength(10),

                        TextInput::make('latitude')
                            ->numeric()
                            ->nullable(),

                        TextInput::make('longitude')
                            ->numeric()
                            ->nullable(),
                    ]),

                Section::make('Business Settings')
                    ->columns(3)
                    ->schema([
                        TextInput::make('platform_fee_rate')
                            ->label('Platform Fee Rate')
                            ->numeric()
                            ->required()
                            ->default(0.05)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.0001)
                            ->helperText('Decimal value — e.g. 0.05 = 5%'),

                        Select::make('settlement_cycle')
                            ->label('Settlement Cycle')
                            ->options(SettlementCycle::options())
                            ->required()
                            ->default(SettlementCycle::WEEKLY->value),

                        Select::make('status')
                            ->options(MerchantStatus::options())
                            ->required()
                            ->default(MerchantStatus::ACTIVE->value),
                    ]),

                Section::make('Banking Details')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextInput::make('bank_name')
                            ->label('Bank Name')
                            ->maxLength(100),

                        TextInput::make('bank_account_number')
                            ->label('Account Number')
                            ->maxLength(255),

                        TextInput::make('bank_account_name')
                            ->label('Account Name')
                            ->maxLength(255),
                    ]),
            ]);
    }
}
